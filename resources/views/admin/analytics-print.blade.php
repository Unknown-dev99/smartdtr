<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — {{ $format === 'graph' ? 'Graph' : 'Table' }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e7eef5;
            color: #142033;
            font-family: Arial, sans-serif;
        }
        .sheet {
            width: 186mm;
            margin: 12px auto;
            background: #fff;
            padding: 10mm 10mm 8mm;
        }
        .format-graph .sheet {
            min-height: 273mm;
            display: flex;
            flex-direction: column;
        }
        header { text-align: center; border-bottom: 3px solid #0D47A1; padding-bottom: 8px; }
        .agency {
            margin: 0;
            font-size: 11px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #4b6278;
        }
        h1 { font-size: 18px; line-height: 1.25; margin: 5px 0 0; }
        h2 { font-size: 22px; line-height: 1.2; margin: 3px 0 0; color: #0D47A1; }
        p.meta { text-align: center; color: #334155; font-size: 13px; margin: 6px 0 0; }
        .stage { margin-top: 6mm; }
        .format-graph .stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .chart-box { width: 100%; height: 195mm; }
        .report-summary .chart-box { width: 168mm; height: 205mm; }
        .note, .foot { margin: 4mm 0 0; font-size: 11px; color: #475569; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 8mm; }
        th, td { border: 1px solid #c5d0dc; padding: 7px 8px; font-size: 12px; }
        th { background: #0D47A1; color: #fff; text-align: left; font-weight: 600; }
        td.num, th.num { text-align: right; }
        tbody tr:nth-child(even) td { background: #f4f7fb; }
        tfoot th { background: #e8eef6; color: #142033; }
        @media print {
            body { background: #fff; }
            .sheet { width: auto; margin: 0; padding: 0; }
            .format-graph .sheet { min-height: 270mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="format-{{ $format }} report-{{ $report }}">
    <article class="sheet">
        <header>
            <p class="agency">Department of Education · Schools Division of the City of Ilagan</p>
            <h1>{{ $analytics['school'] }}</h1>
            <h2>{{ $reportTitle }}</h2>
            <p class="meta">{{ $analytics['from_label'] }} — {{ $analytics['to_label'] }} · {{ $format === 'graph' ? 'Graph' : 'Table' }}</p>
        </header>

        @if($format === 'graph')
            <div class="stage">
                <div class="chart-box">
                    <canvas id="printChart"></canvas>
                </div>
            </div>
            @if($report === 'personnel')
                <p class="note">The graph shows personnel with late or absent days in this period, up to 12 names. Print the table for the full list.</p>
            @endif
            <p class="foot">Person-days from the school attendance report</p>
        @elseif($report === 'summary')
            <table>
                <thead><tr><th>Status</th><th class="num">Person-days</th><th class="num">Share</th></tr></thead>
                <tbody>
                    @foreach($analytics['statuses'] as $status)
                        <tr>
                            <td>{{ $status }}</td>
                            <td class="num">{{ $analytics['summary'][$status] ?? 0 }}</td>
                            <td class="num">{{ $analytics['summary_total'] > 0 ? number_format((($analytics['summary'][$status] ?? 0) / $analytics['summary_total']) * 100, 1) : '0.0' }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="num">{{ $analytics['summary_total'] }}</th>
                        <th class="num">100%</th>
                    </tr>
                </tfoot>
            </table>
        @elseif($report === 'trend')
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        @foreach($analytics['statuses'] as $status)
                            <th class="num">{{ $status }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($analytics['trend'] as $day)
                        <tr>
                            <td>{{ $day['label'] }}</td>
                            @foreach($analytics['statuses'] as $status)
                                <td class="num">{{ $day['counts'][$status] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif($report === 'personnel')
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Group</th>
                        @foreach($analytics['statuses'] as $status)
                            <th class="num">{{ $status }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($analytics['personnel'] as $person)
                        <tr>
                            <td>{{ $person['name'] }}</td>
                            <td>{{ $person['role'] }}</td>
                            <td>{{ $person['group'] }}</td>
                            @foreach($analytics['statuses'] as $status)
                                <td class="num">{{ $person['counts'][$status] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="10">No active personnel in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Group</th>
                        @foreach($analytics['statuses'] as $status)
                            <th class="num">{{ $status }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($analytics['groups'] as $group => $counts)
                        <tr>
                            <td>{{ $group }}</td>
                            @foreach($analytics['statuses'] as $status)
                                <td class="num">{{ $counts[$status] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </article>

    @if($format === 'graph')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script type="application/json" id="office-analytics">{!! json_encode($analytics) !!}</script>
        <script>
        (function () {
            const data = JSON.parse(document.getElementById('office-analytics').textContent);
            const report = @json($report);
            const statuses = data.statuses;
            const colors = statuses.map(status => data.colors[status]);
            const axisFont = { family: 'Arial', size: 12 };
            let config;

            if (report === 'summary') {
                const values = statuses.map(status => data.summary[status] || 0);
                const total = values.reduce((sum, value) => sum + value, 0);
                config = {
                    type: 'doughnut',
                    data: { labels: statuses, datasets: [{ data: values, backgroundColor: colors, borderColor: '#ffffff', borderWidth: 3 }] },
                    options: {
                        cutout: '58%',
                        plugins: {
                            legend: {
                                labels: {
                                    padding: 18,
                                    font: { size: 13, family: 'Arial' },
                                    usePointStyle: true,
                                    pointStyle: 'rectRounded',
                                    generateLabels(chart) {
                                        const dataset = chart.data.datasets[0];
                                        return chart.data.labels.map((label, index) => {
                                            const value = dataset.data[index] || 0;
                                            const share = total ? ((value / total) * 100).toFixed(1) : '0.0';
                                            return {
                                                text: label + '   ' + value + ' (' + share + '%)',
                                                fillStyle: dataset.backgroundColor[index],
                                                strokeStyle: dataset.backgroundColor[index],
                                                fontColor: '#1e293b',
                                                hidden: false,
                                                index
                                            };
                                        });
                                    }
                                }
                            }
                        }
                    },
                    plugins: [{
                        id: 'slicePercent',
                        afterDatasetsDraw(chart) {
                            const { ctx } = chart;
                            const meta = chart.getDatasetMeta(0);
                            meta.data.forEach((arc, index) => {
                                const value = values[index] || 0;
                                if (!total || value / total < 0.06) {
                                    return;
                                }
                                const pos = arc.tooltipPosition();
                                ctx.save();
                                ctx.fillStyle = '#fff';
                                ctx.font = 'bold 15px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(Math.round((value / total) * 100) + '%', pos.x, pos.y);
                                ctx.restore();
                            });
                        }
                    }]
                };
            } else if (report === 'trend') {
                config = {
                    type: 'bar',
                    data: {
                        labels: data.trend.map(day => day.label),
                        datasets: statuses.map(status => ({
                            label: status,
                            data: data.trend.map(day => day.counts[status] || 0),
                            backgroundColor: data.colors[status],
                            borderRadius: 2,
                            maxBarThickness: 36
                        }))
                    },
                    options: {
                        scales: {
                            x: { stacked: true, ticks: { font: axisFont } },
                            y: { stacked: true, beginAtZero: true, ticks: { font: axisFont, precision: 0 } }
                        }
                    }
                };
            } else if (report === 'personnel') {
                const ranked = data.personnel.filter(person => person.late_absent > 0).sort((a, b) => b.late_absent - a.late_absent).slice(0, 12);
                config = {
                    type: 'bar',
                    data: {
                        labels: ranked.map(person => person.name),
                        datasets: [
                            { label: 'Late', data: ranked.map(person => person.counts.Late || 0), backgroundColor: data.colors.Late, borderRadius: 2, maxBarThickness: 18 },
                            { label: 'Absent', data: ranked.map(person => person.counts.Absent || 0), backgroundColor: data.colors.Absent, borderRadius: 2, maxBarThickness: 18 }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        scales: {
                            x: { beginAtZero: true, ticks: { font: axisFont, precision: 0 } },
                            y: { ticks: { font: { family: 'Arial', size: 12 } } }
                        }
                    }
                };
            } else {
                config = {
                    type: 'bar',
                    data: {
                        labels: statuses,
                        datasets: Object.keys(data.groups).map((group, index) => ({
                            label: group,
                            data: statuses.map(status => data.groups[group][status] || 0),
                            backgroundColor: index === 0 ? '#0D47A1' : '#F9A825',
                            borderRadius: 3,
                            maxBarThickness: 42
                        }))
                    },
                    options: {
                        scales: {
                            x: { ticks: { font: axisFont } },
                            y: { beginAtZero: true, ticks: { font: axisFont, precision: 0 } }
                        }
                    }
                };
            }

            config.options = config.options || {};
            config.options.responsive = true;
            config.options.maintainAspectRatio = false;
            config.options.layout = { padding: { top: 8, right: 8, bottom: 4, left: 8 } };
            config.options.animation = { duration: 350, onComplete: () => window.print() };
            config.options.plugins = config.options.plugins || {};
            config.options.plugins.legend = Object.assign({
                position: 'bottom',
                labels: {
                    padding: 16,
                    boxWidth: 14,
                    boxHeight: 12,
                    font: { size: 13, family: 'Arial' },
                    color: '#1e293b'
                }
            }, config.options.plugins.legend || {});

            new Chart(document.getElementById('printChart'), config);
        })();
        </script>
    @else
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
