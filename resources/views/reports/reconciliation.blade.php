<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Reconciliation</title>
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
    <h1>Bank Reconciliation</h1>
    <p class="muted">Statement ID: {{ $st->id }} | Account: {{ $st->bank_account_id }} | Period: {{ $st->period_start }} to {{ $st->period_end }}</p>

    <table>
        <tbody>
            <tr><th>Matched lines</th><td class="right">{{ $matched }}</td></tr>
            <tr><th>Unmatched lines</th><td class="right">{{ $unmatched }}</td></tr>
        </tbody>
    </table>

    <h3>Lines</h3>
    <table>
        <thead>
            <tr>
                <th>Status</th><th>Date</th><th class="right">Amount</th><th>Description</th><th>Counterparty</th><th>Bank Txn ID</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($st->lines as $l)
                <tr>
                    <td>{{ $l->matched_bank_transaction_id ? 'Matched' : 'Unmatched' }}</td>
                    <td>{{ optional($l->posted_at)->toDateString() }}</td>
                    <td class="right">{{ number_format((float)$l->amount, 2) }}</td>
                    <td>{{ $l->description }}</td>
                    <td>{{ $l->counterparty }}</td>
                    <td>{{ $l->matched_bank_transaction_id }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

