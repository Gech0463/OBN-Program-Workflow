const fs = require('fs');
let code = fs.readFileSync('assets/js/app.js', 'utf8');

// Strip any trailing garbage (including weird powershell UTF-16 BOMs or invalid tokens at the end of the file)
code = code.replace(/[\s\S]*?function drawAllCharts.*?\n/s, (match) => {
    return match; // keep everything up to function drawAllCharts
});

// Since the drawAllCharts might be broken, let's just forcefully fix the end
let safeEnd = `
	function drawAllCharts() { 
        document.querySelectorAll('[data-report-chart]').forEach((canvas) => {
		    const context = canvas.getContext('2d');
		    if (!context) return;
		    const rows = JSON.parse(canvas.dataset.reportChart || '[]');
		    const max = Math.max(1, ...rows.map((row) => Number(row.total)));
		    const width = canvas.width = canvas.clientWidth || 420;
		    const height = canvas.height = 220;
		    context.clearRect(0, 0, width, height);
		    rows.forEach((row, index) => { 
                const barWidth = Math.max(12, width / Math.max(rows.length, 1) - 18); 
                const x = index * (width / Math.max(rows.length, 1)) + 9; 
                const barHeight = (Number(row.total) / max) * 155; 
                const y = 178 - barHeight; 
                context.fillStyle = index % 2 ? '#e92732' : '#087f8c'; 
                context.fillRect(x, y, barWidth, barHeight); 
                context.fillStyle = '#17313b'; 
                context.font = '12px sans-serif'; 
                context.fillText(String(row.total), x, y - 6); 
                context.save(); 
                context.translate(x + barWidth / 2, 198); 
                context.rotate(-0.35); 
                context.fillText(String(row.current_status).replaceAll('_', ' '), -35, 0); 
                context.restore(); 
            });
	    });
    }
`;

code = code.replace(/function drawAllCharts\(\) \{[\s\S]*$/, safeEnd);

// Also remove any null bytes or invalid tokens
code = code.replace(/\0/g, '');
code = code.replace(/}[\s\0]*$/g, '}');

fs.writeFileSync('assets/js/app.js', code, 'utf8');
console.log("Fixed");

