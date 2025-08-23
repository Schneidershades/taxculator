<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Statement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 0;
        }

        .muted {
            color: #666;
        }

        .section {
            margin-top: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        pre {
            background: #f7f7f7;
            padding: 10px;
            border: 1px solid #eee;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    @php
    $s = $s ?? [];
    $meta = $s['meta'] ?? [];
    $inputs = $s['inputs'] ?? [];
    $amounts = $s['amounts'] ?? [];
    @endphp

    <h1>Tax Statement</h1>
    <p class="muted">
        Tx ID: {{ $meta['transaction_id'] ?? '' }} |
        Version: {{ $meta['version'] ?? '1' }} |
        Date: {{ $meta['computed_at'] ?? '' }}
    </p>

    <div class="section">
        <h3>Inputs</h3>
        <table>
            <tbody>
                <tr>
                    <th>Country</th>
                    <td>{{ data_get($inputs, 'jurisdiction.country_code') }}</td>
                </tr>
                <tr>
                    <th>State</th>
                    <td>{{ data_get($inputs, 'jurisdiction.state_code') }}</td>
                </tr>
                <tr>
                    <th>Local</th>
                    <td>{{ data_get($inputs, 'jurisdiction.local_code') }}</td>
                </tr>
                <tr>
                    <th>Tax Year</th>
                    <td>{{ data_get($inputs, 'jurisdiction.tax_year') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Amounts</h3>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                'Gross Income' => 'gross_income',
                'Deductions' => 'deductions',
                'Reliefs' => 'reliefs',
                'Taxable Income' => 'taxable_income',
                'Country Tax' => 'country_tax',
                'State Tax' => 'state_tax',
                'Local Tax' => 'local_tax',
                'Total Tax' => 'total_tax',
                ] as $label => $key)
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ number_format((float)($amounts[$key] ?? 0), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Full JSON</h3>
        <pre>{{ json_encode($s, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

</body>

</html>