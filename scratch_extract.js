const fs = require('fs');
let code = fs.readFileSync('dashboard.php', 'utf8');
const operatorBlockStart = code.indexOf("if (user()['role'] !== 'ADMIN'):");
const operatorBlockEnd = code.indexOf("<?php require_once __DIR__.'/includes/footer.php'; exit; endif; ?>");
console.log(code.substring(operatorBlockStart, operatorBlockEnd));

