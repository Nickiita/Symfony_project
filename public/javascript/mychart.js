function renderChart(cnt, x, y, label = 'Stock price change'){

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: x,
            datasets: [{
                label: label,
                data: y,
                borderWidth: 1
            }]
        }
    });
}