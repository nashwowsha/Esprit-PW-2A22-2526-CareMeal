const fs = require('fs');

const insertAdmin = '            <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>\n';
const insertPartner = '            <a href="events.html" class="sidebar-link"><span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements</a>\n';
const insertStudent = '            <a href="events.html" class="sidebar-link">\n              <span class="link-icon"><i class="fa-solid fa-calendar-day"></i></span> Événements\n            </a>\n';

function updateFiles(dir, matchStr, replacement) {
    if (!fs.existsSync(dir)) return;
    fs.readdirSync(dir).filter(f => f.endsWith('.html')).forEach(f => {
        let p = dir + '/' + f;
        let c = fs.readFileSync(p, 'utf8');
        
        let orig = c;
        if (!c.includes('events.html')) {
            c = c.replace(matchStr, replacement + matchStr);
        }

        if (c !== orig) {
            fs.writeFileSync(p, c, 'utf8');
            console.log('Added events link to ' + p);
        }
    });
}

updateFiles('admin', '<a href="logs.html" class="sidebar-link">', insertAdmin);
updateFiles('partner', '<a href="stats.html" class="sidebar-link">', insertPartner);
updateFiles('student', '<a href="orders.html" class="sidebar-link">', insertStudent);
