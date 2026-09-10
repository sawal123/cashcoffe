<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\Menu;
use App\Models\SalesChannel;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditRecipeBranchIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:audit-recipe-branch-integrity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit base and variant recipe branch ownership integrity against operationally saleable branches (READ-ONLY)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // -------------------------------------------------------------------------
        // 1. Load Active Branches & Menus (Full database scope, ignoring auth)
        // -------------------------------------------------------------------------
        $activeBranches = Branch::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $activeBranchesCount = $activeBranches->count();
        $branchesById = $activeBranches->keyBy('id');

        $activeMenus = Menu::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $activeMenusCount = $activeMenus->count();
        $activeMenusById = $activeMenus->keyBy('id');

        // Preload active sales channel IDs
        $activeSalesChannelIds = SalesChannel::where('is_active', true)
            ->pluck('id')
            ->all();

        // Preload priced (menu_id, price_tier_id) pairs on active sales channels
        $pricedMenuTiers = [];
        if (! empty($activeSalesChannelIds)) {
            $menuPrices = DB::table('menu_prices')
                ->whereIn('sales_channel_id', $activeSalesChannelIds)
                ->select('menu_id', 'price_tier_id')
                ->distinct()
                ->get();

            foreach ($menuPrices as $mp) {
                $pricedMenuTiers[$mp->menu_id . '_' . $mp->price_tier_id] = true;
            }
        }

        // Preload unavailable branch_menu entries
        $unavailableBranchMenus = [];
        $unavailableRows = DB::table('branch_menu')
            ->where('is_available', false)
            ->select('branch_id', 'menu_id')
            ->distinct()
            ->get();

        foreach ($unavailableRows as $ubm) {
            $unavailableBranchMenus[$ubm->branch_id . '_' . $ubm->menu_id] = true;
        }

        // -------------------------------------------------------------------------
        // 2. Resolve Operationally Saleable Branches per Menu
        // -------------------------------------------------------------------------
        $saleableBranchesPerMenu = [];
        $saleablePairsCount = 0;

        foreach ($activeMenus as $menu) {
            $saleableBranchesPerMenu[$menu->id] = [];

            foreach ($activeBranches as $branch) {
                // Must have price tier assigned
                if ($branch->price_tier_id === null) {
                    continue;
                }

                // Must have price for branch's price tier on active sales channel
                $hasPrice = isset($pricedMenuTiers[$menu->id . '_' . $branch->price_tier_id]);
                if (! $hasPrice) {
                    continue;
                }

                // Must not be marked unavailable in branch_menu (absence of row = AVAILABLE)
                $isUnavailable = isset($unavailableBranchMenus[$branch->id . '_' . $menu->id]);
                if ($isUnavailable) {
                    continue;
                }

                $saleableBranchesPerMenu[$menu->id][$branch->id] = $branch;
                $saleablePairsCount++;
            }
        }

        // -------------------------------------------------------------------------
        // 3. Diagnostic: Check for Duplicate branch_menu entries
        // -------------------------------------------------------------------------
        $duplicateBranchMenus = DB::table('branch_menu')
            ->select('branch_id', 'menu_id', DB::raw('COUNT(*) as total_count'))
            ->groupBy('branch_id', 'menu_id')
            ->having('total_count', '>', 1)
            ->orderBy('branch_id')
            ->orderBy('menu_id')
            ->get();

        $branchMenuDuplicatesCount = (int) $duplicateBranchMenus->sum(function ($d) {
            return $d->total_count - 1;
        });

        // -------------------------------------------------------------------------
        // 4. Preload Ingredients (Active + Soft-deleted, full database scope)
        // -------------------------------------------------------------------------
        $allIngredients = Ingredients::withoutGlobalScopes()
            ->withTrashed()
            ->get()
            ->keyBy('id');

        // All branches lookup (including inactive) for name display
        $allBranchesLookup = Branch::withoutGlobalScopes()
            ->get()
            ->keyBy('id');

        // Helper to format branch display name
        $formatBranchName = function (?int $branchId) use ($allBranchesLookup): string {
            if ($branchId === null) {
                return 'NULL';
            }
            $b = $allBranchesLookup->get($branchId);
            return $b ? $b->nama_cabang . ' (#' . $b->id . ')' : 'Cabang #' . $branchId;
        };

        // -------------------------------------------------------------------------
        // 5. Base Recipe Audit (menu_ingredients, deleted_at IS NULL)
        // -------------------------------------------------------------------------
        $baseRecipeRows = DB::table('menu_ingredients')
            ->whereNull('deleted_at')
            ->orderBy('menu_id')
            ->orderBy('ingredient_id')
            ->get();

        $baseSourceRowsCount = $baseRecipeRows->count();
        $baseEvaluations = [];
        $baseProblematicRowIds = [];
        $baseOkCount = 0;
        $baseCrossBranchCount = 0;
        $baseBranchlessCount = 0;
        $baseMissingCount = 0;
        $baseSoftDeletedCount = 0;

        foreach ($baseRecipeRows as $recipeRow) {
            $menu = $activeMenusById->get($recipeRow->menu_id);
            if (! $menu) {
                // Inactive or soft-deleted menu is not an operational risk
                continue;
            }

            $saleableBranches = $saleableBranchesPerMenu[$menu->id] ?? [];
            if (empty($saleableBranches)) {
                // Menu not saleable anywhere, not an operational risk
                continue;
            }

            $ingredientId = (int) $recipeRow->ingredient_id;
            $ingredient = $allIngredients->get($ingredientId);

            foreach ($saleableBranches as $branch) {
                $status = $this->classifyIngredientStatus($ingredient, $branch->id);

                if ($status === 'OK') {
                    $baseOkCount++;
                } else {
                    $baseProblematicRowIds[$recipeRow->id] = true;
                    if ($status === 'CROSS_BRANCH') {
                        $baseCrossBranchCount++;
                    } elseif ($status === 'BRANCHLESS_INGREDIENT') {
                        $baseBranchlessCount++;
                    } elseif ($status === 'MISSING_INGREDIENT') {
                        $baseMissingCount++;
                    } elseif ($status === 'SOFT_DELETED_INGREDIENT') {
                        $baseSoftDeletedCount++;
                    }
                }

                $baseEvaluations[] = [
                    'recipe_row_id' => (int) $recipeRow->id,
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->nama_menu,
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->nama_cabang,
                    'ingredient_id' => $ingredientId,
                    'ingredient_name' => $ingredient ? $ingredient->nama_bahan : 'MISSING / UNKNOWN',
                    'ingredient_branch_id' => $ingredient ? $ingredient->branch_id : null,
                    'ingredient_branch_display' => $formatBranchName($ingredient ? $ingredient->branch_id : null),
                    'status' => $status,
                ];
            }
        }

        $baseEvaluatedCount = count($baseEvaluations);
        $baseProblematicRowsCount = count($baseProblematicRowIds);

        // -------------------------------------------------------------------------
        // 6. Variant Recipe Audit (variant_option_ingredients)
        // -------------------------------------------------------------------------
        $variantRecipeRows = DB::table('variant_option_ingredients')
            ->orderBy('variant_option_id')
            ->orderBy('ingredient_id')
            ->get();

        $variantSourceRowsCount = $variantRecipeRows->count();
        $variantOptions = VariantOption::all()->keyBy('id');
        $variantGroups = VariantGroup::all()->keyBy('id');

        $menuVariantGroups = DB::table('menu_variant_group')->get();
        $menuIdsByGroupId = [];
        foreach ($menuVariantGroups as $mvg) {
            $menuIdsByGroupId[$mvg->variant_group_id][] = $mvg->menu_id;
        }

        $variantEvaluations = [];
        $variantProblematicRowIds = [];
        $variantOkCount = 0;
        $variantCrossBranchCount = 0;
        $variantBranchlessCount = 0;
        $variantMissingCount = 0;
        $variantSoftDeletedCount = 0;

        foreach ($variantRecipeRows as $recipeRow) {
            $option = $variantOptions->get($recipeRow->variant_option_id);
            if (! $option) {
                continue;
            }

            $group = $variantGroups->get($option->variant_group_id);
            if (! $group) {
                continue;
            }

            $linkedMenuIds = $menuIdsByGroupId[$group->id] ?? [];
            if (empty($linkedMenuIds)) {
                continue;
            }

            $ingredientId = (int) $recipeRow->ingredient_id;
            $ingredient = $allIngredients->get($ingredientId);

            foreach ($linkedMenuIds as $menuId) {
                $menu = $activeMenusById->get($menuId);
                if (! $menu) {
                    continue;
                }

                $saleableBranches = $saleableBranchesPerMenu[$menu->id] ?? [];
                if (empty($saleableBranches)) {
                    continue;
                }

                foreach ($saleableBranches as $branch) {
                    $status = $this->classifyIngredientStatus($ingredient, $branch->id);

                    if ($status === 'OK') {
                        $variantOkCount++;
                    } else {
                        $variantProblematicRowIds[$recipeRow->id] = true;
                        if ($status === 'CROSS_BRANCH') {
                            $variantCrossBranchCount++;
                        } elseif ($status === 'BRANCHLESS_INGREDIENT') {
                            $variantBranchlessCount++;
                        } elseif ($status === 'MISSING_INGREDIENT') {
                            $variantMissingCount++;
                        } elseif ($status === 'SOFT_DELETED_INGREDIENT') {
                            $variantSoftDeletedCount++;
                        }
                    }

                    $variantEvaluations[] = [
                        'recipe_row_id' => (int) $recipeRow->id,
                        'menu_id' => $menu->id,
                        'menu_name' => $menu->nama_menu,
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->nama_cabang,
                        'variant_group_name' => $group->nama_group,
                        'variant_option_name' => $option->nama_opsi,
                        'ingredient_id' => $ingredientId,
                        'ingredient_name' => $ingredient ? $ingredient->nama_bahan : 'MISSING / UNKNOWN',
                        'ingredient_branch_id' => $ingredient ? $ingredient->branch_id : null,
                        'ingredient_branch_display' => $formatBranchName($ingredient ? $ingredient->branch_id : null),
                        'status' => $status,
                    ];
                }
            }
        }

        $variantEvaluatedCount = count($variantEvaluations);
        $variantProblematicRowsCount = count($variantProblematicRowIds);

        // -------------------------------------------------------------------------
        // 7. Multi-Branch Recipe Risk Classification
        // -------------------------------------------------------------------------
        // A menu is at risk if it is saleable in > 1 branch, while having a recipe item
        // that references a branch-specific ingredient (which cannot be valid in other branches).
        $multiBranchRisks = [];

        // Check base recipe ingredients per menu
        $menuBaseIngredients = [];
        foreach ($baseRecipeRows as $recipeRow) {
            $menuBaseIngredients[$recipeRow->menu_id][] = (int) $recipeRow->ingredient_id;
        }

        // Check variant recipe ingredients per menu
        $menuVariantIngredients = [];
        foreach ($variantRecipeRows as $recipeRow) {
            $opt = $variantOptions->get($recipeRow->variant_option_id);
            if ($opt && isset($menuIdsByGroupId[$opt->variant_group_id])) {
                foreach ($menuIdsByGroupId[$opt->variant_group_id] as $mid) {
                    $menuVariantIngredients[$mid][] = (int) $recipeRow->ingredient_id;
                }
            }
        }

        foreach ($activeMenus as $menu) {
            $saleableBranches = $saleableBranchesPerMenu[$menu->id] ?? [];
            if (count($saleableBranches) <= 1) {
                continue;
            }

            $allRecipeIngredientIds = array_unique(array_merge(
                $menuBaseIngredients[$menu->id] ?? [],
                $menuVariantIngredients[$menu->id] ?? []
            ));

            $hasBranchSpecificIngredient = false;
            $riskDetails = [];

            foreach ($allRecipeIngredientIds as $ingId) {
                $ing = $allIngredients->get($ingId);
                if ($ing && $ing->deleted_at === null && $ing->branch_id !== null) {
                    $hasBranchSpecificIngredient = true;
                    $riskDetails[] = sprintf(
                        'Bahan "%s" (#%d) milik Cabang #%d',
                        $ing->nama_bahan,
                        $ing->id,
                        $ing->branch_id
                    );
                }
            }

            if ($hasBranchSpecificIngredient) {
                $branchNames = array_map(function ($b) {
                    return $b->nama_cabang . ' (#' . $b->id . ')';
                }, $saleableBranches);

                $multiBranchRisks[] = [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->nama_menu,
                    'saleable_branches' => implode(', ', $branchNames),
                    'risk_details' => implode('; ', $riskDetails),
                ];
            }
        }

        $multiBranchRisksCount = count($multiBranchRisks);

        // -------------------------------------------------------------------------
        // 8. Output Deterministic Summary
        // -------------------------------------------------------------------------
        $this->newLine();
        $this->line('==================================================');
        $this->line('RECIPE BRANCH INTEGRITY AUDIT');
        $this->line('==================================================');
        $this->line(sprintf('%-33s: %d', 'Active branches', $activeBranchesCount));
        $this->line(sprintf('%-33s: %d', 'Active menus', $activeMenusCount));
        $this->line(sprintf('%-33s: %d', 'Saleable menu/branch pairs', $saleablePairsCount));
        $this->newLine();
        $this->line(sprintf('%-33s: %d', 'Base source recipe rows', $baseSourceRowsCount));
        $this->line(sprintf('%-33s: %d', 'Base evaluated branch pairs', $baseEvaluatedCount));
        $this->line(sprintf('%-33s: %d', 'Base problematic source rows', $baseProblematicRowsCount));
        $this->newLine();
        $this->line(sprintf('%-33s: %d', 'Base OK', $baseOkCount));
        $this->line(sprintf('%-33s: %d', 'Base CROSS_BRANCH', $baseCrossBranchCount));
        $this->line(sprintf('%-33s: %d', 'Base BRANCHLESS', $baseBranchlessCount));
        $this->line(sprintf('%-33s: %d', 'Base MISSING', $baseMissingCount));
        $this->line(sprintf('%-33s: %d', 'Base SOFT_DELETED', $baseSoftDeletedCount));
        $this->newLine();
        $this->line(sprintf('%-33s: %d', 'Variant source recipe rows', $variantSourceRowsCount));
        $this->line(sprintf('%-33s: %d', 'Variant evaluated menu/branches', $variantEvaluatedCount));
        $this->line(sprintf('%-33s: %d', 'Variant problematic source rows', $variantProblematicRowsCount));
        $this->newLine();
        $this->line(sprintf('%-33s: %d', 'Variant OK', $variantOkCount));
        $this->line(sprintf('%-33s: %d', 'Variant CROSS_BRANCH', $variantCrossBranchCount));
        $this->line(sprintf('%-33s: %d', 'Variant BRANCHLESS', $variantBranchlessCount));
        $this->line(sprintf('%-33s: %d', 'Variant MISSING', $variantMissingCount));
        $this->line(sprintf('%-33s: %d', 'Variant SOFT_DELETED', $variantSoftDeletedCount));
        $this->newLine();
        $this->line(sprintf('%-33s: %d', 'Multi-branch recipe risks', $multiBranchRisksCount));
        $this->line(sprintf('%-33s: %d', 'Branch menu duplicates', $branchMenuDuplicatesCount));
        $this->line(sprintf('%-33s: %d', 'Database changes', 0));
        $this->line('==================================================');
        $this->newLine();

        // -------------------------------------------------------------------------
        // 9. Output Deterministic Issue Details (Deterministic order: menu_id, branch_id, ingredient_id)
        // -------------------------------------------------------------------------
        $baseIssues = array_values(array_filter($baseEvaluations, function ($item) {
            return $item['status'] !== 'OK';
        }));

        usort($baseIssues, function ($a, $b) {
            if ($a['menu_id'] !== $b['menu_id']) {
                return $a['menu_id'] <=> $b['menu_id'];
            }
            if ($a['branch_id'] !== $b['branch_id']) {
                return $a['branch_id'] <=> $b['branch_id'];
            }
            return $a['ingredient_id'] <=> $b['ingredient_id'];
        });

        if (! empty($baseIssues)) {
            $this->warn('BASE RECIPE INTEGRITY ISSUES:');
            $baseTableRows = array_map(function ($row) {
                return [
                    $row['recipe_row_id'],
                    $row['menu_id'],
                    $row['menu_name'],
                    $row['branch_id'],
                    $row['branch_name'],
                    $row['ingredient_id'],
                    $row['ingredient_name'],
                    $row['ingredient_branch_display'],
                    $row['status'],
                ];
            }, $baseIssues);

            $this->table(
                ['Row ID', 'Menu ID', 'Menu', 'Branch ID', 'Branch', 'Ingredient ID', 'Ingredient', 'Ingredient Branch', 'Status'],
                $baseTableRows
            );
            $this->newLine();
        } else {
            $this->info('No base recipe integrity issues found.');
        }

        $variantIssues = array_values(array_filter($variantEvaluations, function ($item) {
            return $item['status'] !== 'OK';
        }));

        usort($variantIssues, function ($a, $b) {
            if ($a['menu_id'] !== $b['menu_id']) {
                return $a['menu_id'] <=> $b['menu_id'];
            }
            if ($a['branch_id'] !== $b['branch_id']) {
                return $a['branch_id'] <=> $b['branch_id'];
            }
            return $a['ingredient_id'] <=> $b['ingredient_id'];
        });

        if (! empty($variantIssues)) {
            $this->warn('VARIANT RECIPE INTEGRITY ISSUES:');
            $variantTableRows = array_map(function ($row) {
                return [
                    $row['recipe_row_id'],
                    $row['menu_id'],
                    $row['menu_name'],
                    $row['branch_id'],
                    $row['branch_name'],
                    $row['variant_group_name'],
                    $row['variant_option_name'],
                    $row['ingredient_id'],
                    $row['ingredient_name'],
                    $row['ingredient_branch_display'],
                    $row['status'],
                ];
            }, $variantIssues);

            $this->table(
                ['Row ID', 'Menu ID', 'Menu', 'Branch ID', 'Branch', 'Variant Group', 'Variant Option', 'Ingredient ID', 'Ingredient', 'Ingredient Branch', 'Status'],
                $variantTableRows
            );
            $this->newLine();
        } else {
            $this->info('No variant recipe integrity issues found.');
        }

        // Multi-Branch Recipe Architectural Risks table
        if (! empty($multiBranchRisks)) {
            $this->warn('MULTI-BRANCH GLOBAL RECIPE ARCHITECTURAL RISKS:');
            $this->comment('Catatan: Menu dijual di >1 cabang tetapi resep menggunakan bahan milik cabang tertentu.');
            $riskTableRows = array_map(function ($row) {
                return [
                    $row['menu_id'],
                    $row['menu_name'],
                    $row['saleable_branches'],
                    $row['risk_details'],
                ];
            }, $multiBranchRisks);

            $this->table(
                ['Menu ID', 'Menu', 'Saleable Branches', 'Risk Details (Branch-Specific Ingredients)'],
                $riskTableRows
            );
            $this->newLine();
        }

        // Diagnostic: Branch Menu Duplicates Table
        if ($duplicateBranchMenus->isNotEmpty()) {
            $this->warn('DIAGNOSTIC: BRANCH MENU DUPLICATE ROWS:');
            $duplicateRows = [];
            foreach ($duplicateBranchMenus as $dup) {
                $b = $allBranchesLookup->get($dup->branch_id);
                $m = $activeMenusById->get($dup->menu_id) ?? Menu::withoutGlobalScopes()->withTrashed()->find($dup->menu_id);
                $duplicateRows[] = [
                    $dup->branch_id,
                    $b ? $b->nama_cabang : 'Cabang #' . $dup->branch_id,
                    $dup->menu_id,
                    $m ? $m->nama_menu : 'Menu #' . $dup->menu_id,
                    $dup->total_count,
                ];
            }

            $this->table(
                ['Branch ID', 'Branch', 'Menu ID', 'Menu', 'Row Count'],
                $duplicateRows
            );
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * Classify an ingredient against a branch ID.
     */
    protected function classifyIngredientStatus(?Ingredients $ingredient, int $branchId): string
    {
        if (! $ingredient) {
            return 'MISSING_INGREDIENT';
        }

        if ($ingredient->deleted_at !== null) {
            return 'SOFT_DELETED_INGREDIENT';
        }

        if ($ingredient->branch_id === null) {
            return 'BRANCHLESS_INGREDIENT';
        }

        if ((int) $ingredient->branch_id !== (int) $branchId) {
            return 'CROSS_BRANCH';
        }

        // Negative stock does not affect ownership integrity
        return 'OK';
    }
}
