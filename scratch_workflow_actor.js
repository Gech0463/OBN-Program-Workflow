const fs = require('fs');
let code = fs.readFileSync('includes/functions.php', 'utf8');

if (!code.includes('function require_workflow_actor')) {
    code = code.replace('?>', `
function require_workflow_actor(string $stageKey): void {
    if (user()['role'] === 'ADMIN') {
        return;
    }
    if (workflow_stage_role($stageKey) !== user()['role']) {
        exit('You do not have permission to process programs at this stage.');
    }
}
?>`);
    fs.writeFileSync('includes/functions.php', code, 'utf8');
    console.log('Added require_workflow_actor');
} else {
    console.log('Already exists');
}

