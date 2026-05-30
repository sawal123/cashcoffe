<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class MenuCompositionPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $menuIds = $this->resolveMenuIds($request);

        $menus = Menu::query()
            ->with([
                'category',
                'ingredients' => fn($query) => $query
                    ->with('satuan')
                    ->orderBy('nama_bahan'),
            ])
            ->when($menuIds, fn($query) => $query->whereIn('id', $menuIds))
            ->orderBy('nama_menu')
            ->get();

        abort_if($menuIds && $menus->isEmpty(), 404, 'Menu tidak ditemukan.');

        $title = count($menuIds) === 1
            ? 'Komposisi Menu - ' . $menus->first()->nama_menu
            : ($menuIds ? 'Komposisi Menu Terpilih' : 'Daftar Komposisi Menu');

        $fileName = Str::slug($title) . '.pdf';

        $pdf = App::make('dompdf.wrapper')
            ->loadView('pdf.menu-compositions', [
                'menus' => $menus,
                'title' => $title,
                'printedAt' => now()->format('d M Y H:i'),
            ])
            ->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    private function resolveMenuIds(Request $request): array
    {
        $ids = $request->query('menus', $request->query('menu'));

        if (! $ids) {
            return [];
        }

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        return collect($ids)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
