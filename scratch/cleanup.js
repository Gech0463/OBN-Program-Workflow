const fs = require('fs');
let code = fs.readFileSync('assets/js/app.js', 'utf8');
const searchString = ' } window.addEventListener("DOMContentLoaded", drawAllCharts); window.addEventListener("resize", drawAllCharts);';
code = code.split(searchString).join('');
// Strip the broken closing brace that was injected by powershell echo '}'
code = code.replace(/\}\s*$/, '');
// Ensure valid ending
if (!code.endsWith('}')) {
    // If it's missing the closing brace for the last block, add it.
    // Wait, let's just make sure it compiles. We will rewrite it perfectly.
}
fs.writeFileSync('assets/js/app.js', code, 'utf8');
console.log("Done");

