const fs = require('fs');

let en = fs.readFileSync('includes/lang/en.php', 'utf8');
let om = fs.readFileSync('includes/lang/om.php', 'utf8');


const newEn = `
    // Program New
    'new_program_journal' => 'New program journal',
    'program_will_start' => 'The program will start at Data Capture.',
    'program_name' => 'Program name',
    'program_type' => 'Program type',
    'select_type' => 'Select a type',
    'department' => 'Department',
    'select_department' => 'Select a department',
    'journalist_owner' => 'Journalist / owner',
    'select_journalist' => 'Select journalist',
    'presenter' => 'Presenter',
    'language' => 'Language',
    'source' => 'Source',
    'priority' => 'Priority',
    'normal' => 'Normal',
    'low' => 'Low',
    'high' => 'High',
    'urgent' => 'Urgent',
    'program_date_start' => 'Program date and start',
    'scheduled_broadcast' => 'Scheduled broadcast',
    'expected_duration' => 'Expected duration (minutes)',
    'initial_location' => 'Initial location',
    'description_notes' => 'Description and notes',
    'create_program_journal' => 'Create program journal',
`;

const newOm = `
    // Program New
    'new_program_journal' => 'Galmee sagantaa haaraa',
    'program_will_start' => 'Sagantaan kun Daataa Fudhachuu irraa eegala.',
    'program_name' => 'Maqaa sagantaa',
    'program_type' => 'Gosa sagantaa',
    'select_type' => 'Gosa filadhu',
    'department' => 'Kutaa',
    'select_department' => 'Kutaa filadhu',
    'journalist_owner' => 'Gaazexeessaa / abbaa',
    'select_journalist' => 'Gaazexeessaa filadhu',
    'presenter' => 'Dhiheessaa',
    'language' => 'Afaan',
    'source' => 'Madda',
    'priority' => 'Dursa',
    'normal' => 'Idilee',
    'low' => 'Gadi aanaa',
    'high' => 'Guddaa',
    'urgent' => 'Ariifachiisaa',
    'program_date_start' => 'Guyyaa fi jalqaba sagantaa',
    'scheduled_broadcast' => 'Tamsaasa karoorfame',
    'expected_duration' => 'Turtii eegamu (daqiiqaa)',
    'initial_location' => 'Iddoo jalqabaa',
    'description_notes' => 'Ibsaa fi yaada',
    'create_program_journal' => 'Galmee sagantaa uumi',
`;

if (!en.includes('new_program_journal')) {
    fs.writeFileSync('includes/lang/en.php', en.replace('];', newEn + '];'));
    fs.writeFileSync('includes/lang/om.php', om.replace('];', newOm + '];'));
}

let code = fs.readFileSync('program_new.php', 'utf8');

const replacements = {
    '>New program journal<': '><?= e(__(\'new_program_journal\')) ?><',
    '>The program will start at Data Capture.<': '><?= e(__(\'program_will_start\')) ?><',
    'Program name': '<?= e(__(\'program_name\')) ?>',
    'Program type': '<?= e(__(\'program_type\')) ?>',
    '>Select a type<': '><?= e(__(\'select_type\')) ?><',
    'Department\\r\\n': '<?= e(__(\'department\')) ?>\\r\\n',
    'Department\\n': '<?= e(__(\'department\')) ?>\\n',
    '>Select a department<': '><?= e(__(\'select_department\')) ?><',
    'Journalist / owner': '<?= e(__(\'journalist_owner\')) ?>',
    '>Select journalist<': '><?= e(__(\'select_journalist\')) ?><',
    '>Presenter<': '><?= e(__(\'presenter\')) ?><',
    '>Language<': '><?= e(__(\'language\')) ?><',
    '>Source<': '><?= e(__(\'source\')) ?><',
    'Priority\\r\\n': '<?= e(__(\'priority\')) ?>\\r\\n',
    'Priority\\n': '<?= e(__(\'priority\')) ?>\\n',
    '>Normal<': '><?= e(__(\'normal\')) ?><',
    '>Low<': '><?= e(__(\'low\')) ?><',
    '>High<': '><?= e(__(\'high\')) ?><',
    '>Urgent<': '><?= e(__(\'urgent\')) ?><',
    '>Program date and start<': '><?= e(__(\'program_date_start\')) ?><',
    '>Scheduled broadcast<': '><?= e(__(\'scheduled_broadcast\')) ?><',
    '>Expected duration (minutes)<': '><?= e(__(\'expected_duration\')) ?><',
    '>Initial location<': '><?= e(__(\'initial_location\')) ?><',
    'Description and notes': '<?= e(__(\'description_notes\')) ?>',
    '>Create program journal<': '><?= e(__(\'create_program_journal\')) ?><'
};

for (const [key, value] of Object.entries(replacements)) {
    code = code.split(key).join(value);
}

fs.writeFileSync('program_new.php', code, 'utf8');
console.log('Translated program_new.php');

