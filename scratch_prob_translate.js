const fs = require('fs');

let en = fs.readFileSync('includes/lang/en.php', 'utf8');
let om = fs.readFileSync('includes/lang/om.php', 'utf8');

const newEn = `
    // Dashboard Problems
    'problems_attention' => 'Problems requiring attention',
    'problems_attention_desc' => 'Open reports from employees. Review and resolve them here.',
    'manage_problems' => 'Manage all problems',
    'no_open_problems' => 'No open problems',
    'no_open_problems_desc' => 'All reported issues have been resolved or closed.',
`;

const newOm = `
    // Dashboard Problems
    'problems_attention' => 'Rakkoolee xiyyeeffannoo barbaadan',
    'problems_attention_desc' => 'Gabaasaalee hojjettoota irraa dhufan asitti ilaaliitii furmaata kenni.',
    'manage_problems' => 'Rakkoolee hunda to\\'adhu',
    'no_open_problems' => 'Rakkoon banamaa hin jiru',
    'no_open_problems_desc' => 'Rakkooleen gabaafaman hundi furamaniiru ykn cufamaniiru.',
`;

if (!en.includes('problems_attention')) {
    // Make sure we have a trailing comma
    en = en.replace('];', newEn + '];');
    om = om.replace('];', newOm + '];');
    fs.writeFileSync('includes/lang/en.php', en);
    fs.writeFileSync('includes/lang/om.php', om);
}

let code = fs.readFileSync('dashboard.php', 'utf8');
code = code.replace('>Problems requiring attention<', '><?= e(__(\'problems_attention\')) ?><');
code = code.replace('>Open reports from employees. Review and resolve them here.<', '><?= e(__(\'problems_attention_desc\')) ?><');
code = code.replace('>Manage all problems<', '><?= e(__(\'manage_problems\')) ?><');
code = code.replace('>No open problems<', '><?= e(__(\'no_open_problems\')) ?><');
code = code.replace('>All reported issues have been resolved or closed.<', '><?= e(__(\'no_open_problems_desc\')) ?><');

fs.writeFileSync('dashboard.php', code, 'utf8');
console.log('Translated problems on dashboard.php');

