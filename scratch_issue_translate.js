const fs = require('fs');

let en = fs.readFileSync('includes/lang/en.php', 'utf8');
let om = fs.readFileSync('includes/lang/om.php', 'utf8');

const newEn = `
    // Header & Notifications
    'updates' => 'UPDATES',
    'notifications_title' => 'Notifications',
    'unread' => 'unread',
    'select_message_read' => 'Select a message to mark it read.',
    
    // Issues
    'problem_management' => 'Problem management',
    'problem_management_desc' => 'Review, assign, resolve, and close problems reported by employees.',
    'open_status' => 'Open',
    'needs_assignment' => 'Needs assignment',
    'being_handled' => 'Being handled',
    'resolved_closed' => 'Resolved / closed',
    'completed_reports' => 'Completed reports',
    'issue_register' => 'Issue register',
`;

const newOm = `
    // Header & Notifications
    'updates' => 'ODEEFFANNOO HAARAA',
    'notifications_title' => 'Beeksisoota',
    'unread' => 'hin dubbifamne',
    'select_message_read' => 'Ergaa dubbisuuf filadhu.',
    
    // Issues
    'problem_management' => 'To\\'annoo rakkoo',
    'problem_management_desc' => 'Rakkoolee uumaman ilaali, ramadi, furmaata itti barbaadi.',
    'open_status' => 'Banaa',
    'needs_assignment' => 'Kan hin ramadamiin',
    'being_handled' => 'Hojjetamaa jira',
    'resolved_closed' => 'Furame / Cufame',
    'completed_reports' => 'Gabaasa xumurame',
    'issue_register' => 'Galmee Rakkoo',
`;

if (!en.includes('problem_management')) {
    fs.writeFileSync('includes/lang/en.php', en.replace('];', newEn + '];'));
    fs.writeFileSync('includes/lang/om.php', om.replace('];', newOm + '];'));
}

// Fix Header
let header = fs.readFileSync('includes/header.php', 'utf8');
header = header.replace(/<label class="visually-hidden" for="global-search">Search programs<\/label>/g, '');
header = header.replace(/<i class="fa-solid [^"]+"><\/i> /g, '');
header = header.replace('>UPDATES<', '><?= e(__(\'updates\')) ?><');
header = header.replace('>Notifications<', '><?= e(__(\'notifications_title\')) ?><');
header = header.replace('>unread<', '><?= e(__(\'unread\')) ?><');
header = header.replace('>Select a message to mark it read.<', '><?= e(__(\'select_message_read\')) ?><');
fs.writeFileSync('includes/header.php', header, 'utf8');

// Fix Issues
let issues = fs.readFileSync('issues.php', 'utf8');
issues = issues.replace('>Problem management<', '><?= e(__(\'problem_management\')) ?><');
issues = issues.replace('>Review, assign, resolve, and close problems reported by employees.<', '><?= e(__(\'problem_management_desc\')) ?><');
issues = issues.replace('>Open<', '><?= e(__(\'open_status\')) ?><');
issues = issues.replace('>Needs assignment<', '><?= e(__(\'needs_assignment\')) ?><');
issues = issues.replace('>In progress<', '><?= e(__(\'in_progress\')) ?><');
issues = issues.replace('>Being handled<', '><?= e(__(\'being_handled\')) ?><');
issues = issues.replace('>Resolved / closed<', '><?= e(__(\'resolved_closed\')) ?><');
issues = issues.replace('>Completed reports<', '><?= e(__(\'completed_reports\')) ?><');
issues = issues.replace('>Issue register<', '><?= e(__(\'issue_register\')) ?><');
fs.writeFileSync('issues.php', issues, 'utf8');

console.log('Fixed header, sidebar icons, and translated issues.php');

