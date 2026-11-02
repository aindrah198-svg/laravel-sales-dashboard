<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    private function data(Request $r): array
    {
        $v = $r->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'status' => ['nullable', Rule::in(['Paid', 'Pending', 'Refunded'])], 'q' => 'nullable|string|max:100']);
        $from = $v['from'] ?? today()->subDays(29)->toDateString();
        $to = $v['to'] ?? today()->toDateString();
        if ($from > $to) {
            throw ValidationException::withMessages(['to' => 'End date must be on or after start date.']);
        }
        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 365) {
            throw ValidationException::withMessages(['to' => 'Choose a range of at most 366 days.']);
        }
        $query = Sale::query()->whereBetween('ordered_at', [$from, $to]);
        if (! empty($v['status'])) {
            $query->where('status', $v['status']);
        }
        if (! empty($v['q'])) {
            $needle = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $v['q']);
            $query->where(function ($b) use ($needle) {
                $b->whereRaw("customer LIKE ? ESCAPE '!'", ['%'.$needle.'%'])->orWhereRaw("reference LIKE ? ESCAPE '!'", ['%'.$needle.'%']);
            });
        }

        return [$query, $from, $to];
    }

    public function index(Request $r)
    {
        [$query,$from,$to] = $this->data($r);
        $count = (clone $query)->count();
        $paid = (clone $query)->where('status', 'Paid');
        $revenue = (clone $paid)->sum('amount_cents');
        $paidCount = (clone $paid)->count();
        $pending = (clone $query)->where('status', 'Pending')->sum('amount_cents');
        $daily = (clone $paid)->selectRaw('ordered_at, SUM(amount_cents) as total')->groupBy('ordered_at')->pluck('total', 'ordered_at');
        $chart = [];
        for ($d = Carbon::parse($from); $d->lte(Carbon::parse($to)); $d->addDay()) {
            $chart[] = ['date' => $d->toDateString(), 'label' => $d->format('M j'), 'value' => (int) ($daily[$d->toDateString()] ?? 0)];
        }
        $channels = (clone $paid)->selectRaw('channel, SUM(amount_cents) as total')->groupBy('channel')->orderByDesc('total')->get();
        $sales = (clone $query)->orderByDesc('ordered_at')->orderByDesc('id')->paginate(10)->withQueryString();

        return view('dashboard', compact('from', 'to', 'count', 'revenue', 'paidCount', 'pending', 'chart', 'channels', 'sales'));
    }

    public function export(Request $r)
    {
        [$query] = $this->data($r);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Order', 'Date', 'Customer', 'Product', 'Channel', 'Status', 'Amount USD'], ',', '"', '');
            foreach ($query->orderBy('id')->cursor() as $sale) {
                $row = [$sale->reference, $sale->ordered_at->format('Y-m-d'), $sale->customer, $sale->product, $sale->channel, $sale->status, number_format($sale->amount_cents / 100, 2, '.', '')];
                $row = array_map(fn ($s) => preg_match('/^[\s]*[=+@-]/u', (string) $s) ? "'".$s : $s, $row);
                fputcsv($out, $row, ',', '"', '');
            } fclose($out);
        }, 'sales-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
