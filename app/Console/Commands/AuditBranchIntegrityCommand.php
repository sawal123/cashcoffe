<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditBranchIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:audit-branch-integrity {--apply : Apply deterministic repairs to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and deterministically repair missing branch_id on ingredients and riwayat_stocks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? 'Starting Branch Integrity Audit & Repair (--apply mode)...' : 'Starting Branch Integrity Audit (DRY RUN mode)...');

        // =========================================================================
        // PHASE 1: Audit Ingredients where branch_id IS NULL
        // =========================================================================
        $nullIngredients = Ingredients::withoutGlobalScopes()
            ->withTrashed()
            ->whereNull('branch_id')
            ->orderBy('id')
            ->get();

        $ingredientAudits = [];
        $safeIngredients = [];

        foreach ($nullIngredients as $ingredient) {
            $lifecycle = $ingredient->trashed() ? 'TRASHED' : 'ACTIVE';

            // Ambil seluruh riwayat stok (termasuk soft-deleted) yang mempunyai branch_id tidak null
            $distinctBranchIds = RiwayatStock::withoutGlobalScopes()
                ->withTrashed()
                ->where('ingredient_id', $ingredient->id)
                ->whereNotNull('branch_id')
                ->pluck('branch_id')
                ->unique()
                ->values()
                ->all();

            // Diagnostic only: Menu yang tertaut (bukan penentu candidate branch)
            $linkedMenus = [];
            try {
                $linkedMenus = $ingredient->menus()
                    ->withoutGlobalScopes()
                    ->withTrashed()
                    ->pluck('nama_menu')
                    ->all();
            } catch (\Throwable $e) {
                // Ignore diagnostic error if pivot issue occurs
            }

            $candidateBranchId = null;
            $status = 'UNRESOLVED';
            $reason = '';

            if (count($distinctBranchIds) === 1) {
                $candidate = (int) $distinctBranchIds[0];
                // Cek apakah branch tersebut memang ada di tabel branches (tidak membatasi is_active)
                $branchExists = Branch::withoutGlobalScopes()->where('id', $candidate)->exists();

                if ($branchExists) {
                    $status = 'SAFE_TO_REPAIR';
                    $candidateBranchId = $candidate;
                    $reason = "Single distinct branch from stock history [{$candidate}]";
                } else {
                    $status = 'UNRESOLVED';
                    $reason = "Branch ID {$candidate} from stock history no longer exists in branches table";
                }
            } elseif (count($distinctBranchIds) > 1) {
                $status = 'CONFLICT';
                $reason = 'Conflicting stock history branches: [' . implode(', ', $distinctBranchIds) . ']';
            } else {
                $status = 'UNRESOLVED';
                $reason = 'No deterministic stock history evidence';
            }

            $auditItem = [
                'id' => $ingredient->id,
                'nama_bahan' => $ingredient->nama_bahan,
                'lifecycle' => $lifecycle,
                'candidate_branch' => $candidateBranchId,
                'candidate_branches_raw' => $distinctBranchIds,
                'status' => $status,
                'reason' => $reason,
                'linked_menus' => $linkedMenus,
                'model' => $ingredient,
            ];

            $ingredientAudits[] = $auditItem;

            if ($status === 'SAFE_TO_REPAIR') {
                $safeIngredients[] = $auditItem;
            }
        }

        // =========================================================================
        // PHASE 2 & 3: Audit RiwayatStock where branch_id IS NULL & Apply Transaction
        // =========================================================================
        $repairedIngredientsCount = 0;
        $repairedHistoriesCount = 0;

        if ($apply) {
            DB::transaction(function () use (
                &$safeIngredients,
                &$repairedIngredientsCount,
                &$repairedHistoriesCount,
                &$historyAudits
            ) {
                // 1. Repair safe ingredients first
                foreach ($safeIngredients as &$item) {
                    Ingredients::withoutGlobalScopes()
                        ->withTrashed()
                        ->where('id', $item['id'])
                        ->update(['branch_id' => $item['candidate_branch']]);

                    // Update in-memory state agar Phase 3 dapat melihat branch yang baru di-repair
                    $item['model']->branch_id = $item['candidate_branch'];
                    $repairedIngredientsCount++;
                }
                unset($item);

                // 2. Audit & Repair riwayat_stocks
                $historyAudits = $this->auditHistories();

                foreach ($historyAudits as $hItem) {
                    if ($hItem['status'] === 'SAFE_TO_REPAIR') {
                        RiwayatStock::withoutGlobalScopes()
                            ->withTrashed()
                            ->where('id', $hItem['id'])
                            ->update(['branch_id' => $hItem['candidate_branch']]);

                        $repairedHistoriesCount++;
                    }
                }
            });
        } else {
            // DRY RUN: Audit riwayat_stocks secara read-only
            // Pada dry-run, jika ingredient safe_to_repair, simulasikan candidate branch
            $simulatedIngredientBranches = [];
            foreach ($safeIngredients as $item) {
                $simulatedIngredientBranches[$item['id']] = $item['candidate_branch'];
            }

            $historyAudits = $this->auditHistories($simulatedIngredientBranches);
        }

        // =========================================================================
        // SUMMARY & REPORTING
        // =========================================================================
        $ingNullCount = count($ingredientAudits);
        $ingSafeCount = count(array_filter($ingredientAudits, fn ($i) => $i['status'] === 'SAFE_TO_REPAIR'));
        $ingConflictCount = count(array_filter($ingredientAudits, fn ($i) => $i['status'] === 'CONFLICT'));
        $ingUnresolvedCount = count(array_filter($ingredientAudits, fn ($i) => $i['status'] === 'UNRESOLVED'));

        $histNullCount = count($historyAudits);
        $histSafeCount = count(array_filter($historyAudits, fn ($h) => $h['status'] === 'SAFE_TO_REPAIR'));
        $histUnresolvedCount = count(array_filter($historyAudits, fn ($h) => $h['status'] === 'UNRESOLVED'));
        $histOrphanCount = count(array_filter($historyAudits, fn ($h) => $h['status'] === 'ORPHAN'));

        $totalChanges = $apply ? ($repairedIngredientsCount + $repairedHistoriesCount) : 0;

        $this->newLine();
        $this->line('==================================================');
        $this->line('BRANCH INTEGRITY AUDIT');
        $this->line('==================================================');
        $this->line(sprintf('Ingredients NULL  : %d', $ingNullCount));
        $this->line(sprintf('  Safe to repair  : %d', $ingSafeCount));
        $this->line(sprintf('  Conflicting     : %d', $ingConflictCount));
        $this->line(sprintf('  Unresolved      : %d', $ingUnresolvedCount));
        $this->line(sprintf('RiwayatStock NULL : %d', $histNullCount));
        $this->line(sprintf('  Safe to repair  : %d', $histSafeCount));
        $this->line(sprintf('  Unresolved      : %d', $histUnresolvedCount));
        if ($histOrphanCount > 0) {
            $this->line(sprintf('  Orphan          : %d', $histOrphanCount));
        }
        $this->line(sprintf('Mode              : %s', $apply ? 'APPLY' : 'DRY RUN'));
        $this->line(sprintf('Database changes  : %d', $totalChanges));
        $this->line('==================================================');
        $this->newLine();

        // Tampilkan Detail Ingredients yang bermasalah jika ada
        if (! empty($ingredientAudits)) {
            $this->info('--- INGREDIENTS AUDIT DETAIL ---');
            $tableData = array_map(function ($i) {
                $menus = ! empty($i['linked_menus']) ? implode(', ', $i['linked_menus']) : '-';
                return [
                    $i['id'],
                    $i['nama_bahan'],
                    $i['lifecycle'],
                    $i['candidate_branch'] ?? '-',
                    $i['status'],
                    $i['reason'],
                    $menus,
                ];
            }, $ingredientAudits);

            $this->table(
                ['ID', 'Nama Bahan', 'Lifecycle', 'Candidate Branch', 'Status', 'Reason', 'Linked Menus (Diagnostic)'],
                $tableData
            );
            $this->newLine();
        }

        // Tampilkan Unresolved/Orphan Histories jika ada
        $unresolvedHistories = array_filter($historyAudits, fn ($h) => $h['status'] !== 'SAFE_TO_REPAIR');
        if (! empty($unresolvedHistories)) {
            $this->info('--- UNRESOLVED / ORPHAN HISTORIES ---');
            $hTableData = array_map(function ($h) {
                return [
                    $h['id'],
                    $h['ingredient_id'],
                    $h['lifecycle'],
                    $h['status'],
                    $h['reason'],
                ];
            }, array_slice($unresolvedHistories, 0, 50)); // limit preview to 50 items

            $this->table(
                ['History ID', 'Ingredient ID', 'Lifecycle', 'Status', 'Reason'],
                $hTableData
            );

            if (count($unresolvedHistories) > 50) {
                $this->warn(sprintf('Showing first 50 of %d unresolved histories.', count($unresolvedHistories)));
            }
            $this->newLine();
        }

        // Manual Review Required Section
        $manualReviewIngredients = array_filter($ingredientAudits, fn ($i) => in_array($i['status'], ['CONFLICT', 'UNRESOLVED'], true));
        if (! empty($manualReviewIngredients)) {
            $this->warn('MANUAL REVIEW REQUIRED FOR INGREDIENTS:');
            foreach ($manualReviewIngredients as $m) {
                $this->line(sprintf(' - Ingredient #%d — %s | Reason: %s', $m['id'], $m['nama_bahan'], $m['reason']));
            }
            $this->newLine();
        }

        if (! $apply && ($ingSafeCount > 0 || $histSafeCount > 0)) {
            $this->comment('This was a DRY RUN. Run with --apply to execute deterministic database repairs.');
        } elseif ($apply) {
            $this->info(sprintf('Successfully applied %d repairs (%d ingredients, %d histories).', $totalChanges, $repairedIngredientsCount, $repairedHistoriesCount));
        }

        return self::SUCCESS;
    }

    /**
     * Audit riwayat stocks with null branch_id.
     *
     * @param array<int, int> $simulatedParentBranches In-memory parent branches for dry-run simulation
     * @return array<int, array<string, mixed>>
     */
    protected function auditHistories(array $simulatedParentBranches = []): array
    {
        $nullHistories = RiwayatStock::withoutGlobalScopes()
            ->withTrashed()
            ->whereNull('branch_id')
            ->orderBy('id')
            ->get();

        $historyAudits = [];

        foreach ($nullHistories as $history) {
            $lifecycle = $history->trashed() ? 'TRASHED' : 'ACTIVE';

            $parent = Ingredients::withoutGlobalScopes()
                ->withTrashed()
                ->find($history->ingredient_id);

            $candidateBranch = null;
            $status = 'UNRESOLVED';
            $reason = '';

            if (! $parent) {
                $status = 'ORPHAN';
                $reason = "Parent ingredient #{$history->ingredient_id} not found";
            } else {
                // Cek apakah parent sudah punya branch_id di DB atau disimulasikan dari repair ingredient
                $parentBranch = $parent->branch_id ?? ($simulatedParentBranches[$parent->id] ?? null);

                if ($parentBranch !== null) {
                    $status = 'SAFE_TO_REPAIR';
                    $candidateBranch = (int) $parentBranch;
                    $reason = "Parent ingredient #{$parent->id} resolved to branch #{$candidateBranch}";
                } else {
                    $status = 'UNRESOLVED';
                    $reason = "Parent ingredient #{$parent->id} has no branch (unresolved or conflict)";
                }
            }

            $historyAudits[] = [
                'id' => $history->id,
                'ingredient_id' => $history->ingredient_id,
                'lifecycle' => $lifecycle,
                'candidate_branch' => $candidateBranch,
                'status' => $status,
                'reason' => $reason,
                'model' => $history,
            ];
        }

        return $historyAudits;
    }
}
