<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>General Ledger</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>General Ledger</h1>
    <p class="muted">Period: {{ $from ?: '-' }} to {{ $to ?: '-' }}</p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Txn</th>
                <th>Account</th>
                <th class="right">Debit</th>
                <th class="right">Credit</th>
                <th>Narrative</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $e)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($e->occurred_at)->toDateString() }}</td>
                    <td>{{ $e->transaction_id }}</td>
                    <td>{{ $e->code }} &mdash; {{ $e->name }}</td>
                    <td class="right">{{ number_format((float)$e->debit, 2) }}</td>
                    <td class="right">{{ number_format((float)$e->credit, 2) }}</td>
                    <td>{{ $e->narrative }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

