<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Form Submission</title>
    <style>
        body { font-family: sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #334155; }
        .card { background: #fff; border-radius: 8px; padding: 24px; max-width: 560px; margin: 0 auto; border: 1px solid #e2e8f0; }
        h2 { margin: 0 0 4px; font-size: 18px; color: #0f172a; }
        p.subtitle { color: #64748b; font-size: 14px; margin: 0 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; color: #64748b; font-weight: 500; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
        td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: top; }
        td:first-child { font-weight: 500; color: #475569; width: 36%; padding-right: 12px; }
        .footer { margin-top: 20px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="card">
    <h2>New Submission: {{ $form->name }}</h2>
    <p class="subtitle">Received {{ $submission->created_at->format('d M Y, H:i') }}{{ $submission->ip_address ? ' from ' . $submission->ip_address : '' }}</p>

    <table>
        <tbody>
            @foreach($form->fields as $field)
                @php $value = $submission->data[$field['name']] ?? null; @endphp
                @if($value !== null && $value !== '')
                    <tr>
                        <td>{{ $field['label'] }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <p class="footer">
        This email was sent by Bintan Prestige CMS — Contact Form Builder plugin.
    </p>
</div>
</body>
</html>
