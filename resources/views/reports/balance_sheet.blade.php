<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
        .section { margin-top: 16px; }
        .right { text-align: right; }
    </style>
    </head>
<body>
    <h1>Balance Sheet</h1>
    <p class="muted">As of: {{ $asOf }}</p>

    @php($sections = ['assets' => 'Assets', 'liabilities' => 'Liabilities', 'equity' => 'Equity'])

    @foreach ($sections as $key => $label)
        <div class="section">
            <h3>{{ $label }}</h3>
            <table>
                <thead><tr><th>Account</th><th class="right">Amount</th></tr></thead>
                <tbody>
                @foreach ($data[$key]['lines'] as $l)
                    <tr>
                        <td>{{ $l['code'] }} &mdash; {{ $l['name'] }}</td>
                        <td class="right">{{ number_format((float)$l['amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <th>Total {{ $label }}</th>
                    <th class="right">{{ number_format((float)$data[$key]['total'], 2) }}</th>
                </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="section">
        <table>
            <tbody>
            <tr>
                <th>Balance check (A = L + E)</th>
                <td class="right">{{ number_format((float)$data['assets']['total'] - (float)$data['liabilities']['total'] - (float)$data['equity']['total'], 2) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</body>
</html>

