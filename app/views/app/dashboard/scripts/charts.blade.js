const statsChart = JSON.parse(`{!! $statsChart !!}`);

const formTitles = statsChart.map(item => item.form.title);
const submissionCounts = statsChart.map(item => item.count);

// Initialize the ECharts instance
const chartDom = document.getElementById('submissionBarChart');
const myChart = echarts.init(chartDom);

// Chart configuration
const option = {
    title: {
        text: 'Form Submission Stats',
    },
    tooltip: {
        trigger: 'axis',
    },
    xAxis: {
        type: 'category',
        data: formTitles,
        axisLabel: {
            rotate: 45, // Rotate labels for better readability
            interval: 0, // Show all labels
        }
    },
    yAxis: {
        type: 'value',
        name: 'Submission Count',
    },
    series: [
        {
            name: 'Submissions',
            type: 'bar',
            data: submissionCounts,
            itemStyle: {
                color: '#4a90e2', // Customize bar color
            },
        },
    ],
};

// Render the chart
myChart.setOption(option);