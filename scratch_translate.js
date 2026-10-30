const fs = require('fs');

function translateFile(filename, replacements) {
    try {
        let code = fs.readFileSync(filename, 'utf8');
        for (const [key, value] of Object.entries(replacements)) {
            code = code.split(key).join(value);
        }
        fs.writeFileSync(filename, code, 'utf8');
        console.log(`Translated ${filename}`);
    } catch (e) {
        console.error(`Failed to translate ${filename}:`, e.message);
    }
}

const adminReplacements = {
    '>Organization<': '><?= e(__(\'organization\')) ?><',
    '>Rooms<': '><?= e(__(\'rooms\')) ?><',
    '>Workflow Stages<': '><?= e(__(\'workflow_stages\')) ?><',
    '>Users &amp; Access<': '><?= e(__(\'users_access\')) ?><',
    '>Add department<': '><?= e(__(\'add_department\')) ?><',
    'placeholder="Department name"': 'placeholder="<?= e(__(\'department_name\')) ?>"',
    'placeholder="Type name"': 'placeholder="<?= e(__(\'type_name\')) ?>"',
    '>Departments<': '><?= e(__(\'departments\')) ?><',
    '>Remove<': '><?= e(__(\'remove\')) ?><',
    '>Add program type<': '><?= e(__(\'add_program_type\')) ?><',
    '>Program types<': '><?= e(__(\'program_types\')) ?><',
    '>Add room<': '><?= e(__(\'add_room\')) ?><',
    'placeholder="Room name"': 'placeholder="<?= e(__(\'room_name\')) ?>"',
    '>Add stage<': '><?= e(__(\'add_stage\')) ?><',
    'placeholder="Stage name"': 'placeholder="<?= e(__(\'stage_name\')) ?>"',
    '>Add user<': '><?= e(__(\'add_user\')) ?><'
};

translateFile('admin.php', adminReplacements);

