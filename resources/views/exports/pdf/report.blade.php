<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $document['title'] }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 20px; margin-bottom: 4px; }
    h2 { font-size: 14px; margin-top: 18px; margin-bottom: 4px; }
    p.note { font-size: 10px; color: #555; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background-color: #eee; }
    dl.meta { margin: 8px 0; }
    dl.meta dt { font-weight: bold; display: inline; }
    dl.meta dd { display: inline; margin: 0 16px 0 4px; }
</style>
</head>
<body>
<h1>{{ $document['title'] }}</h1>
<dl class="meta">
@foreach ($document['meta'] as $item)
    <dt>{{ $item['label'] }}</dt><dd>{{ $item['value'] }}</dd>
@endforeach
</dl>
@foreach ($document['sections'] as $section)
    <h2>{{ $section['heading'] }}</h2>
    @if ($section['note'] !== null)
        <p class="note">{{ $section['note'] }}</p>
    @endif
    @if (count($section['rows']) === 0)
        <p class="note">No data.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($section['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($section['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endforeach
</body>
</html>
