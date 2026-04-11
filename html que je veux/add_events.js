const fs = require('fs');

['admin','student','partner'].forEach(d => {
    if (!fs.existsSync(d)) return;
    fs.readdirSync(d).filter(f => f.endsWith('.html')).forEach(f => {
        let p = d + '/' + f;
        let c = fs.readFileSync(p, 'utf8');
        let orig = c;
        
        // Remove existing events link entirely
        c = c.replace(/<a href=""events\.html""[^>]*>[\s\S]*?<\/a>\s+/g, '');
        
        if (d === 'admin') {
            c = c.replace(/(<a href=""logs\.html"")/g, '  <a href=""events.html"" class=""sidebar-link""><span class=""link-icon""><i class=""fa-solid fa-calendar-day""></i></span> Événements</a>\n            ');
        } else if (d === 'partner') {
            c = c.replace(/(<a href=""stats\.html"")/g, '  <a href=""events.html"" class=""sidebar-link""><span class=""link-icon""><i class=""fa-solid fa-calendar-day""></i></span> Événements</a>\n            ');
        } else if (d === 'student') {
            c = c.replace(/(<a href=""orders\.html"")/g, '  <a href=""events.html"" class=""sidebar-link"">\n              <span class=""link-icon""><i class=""fa-solid fa-calendar-day""></i></span> Événements\n            </a>\n            ');
        }

        // Clean previous encoding glitches
        c = c.replace(/ï¿½vï¿½nements/g, 'Événements');
        c = c.replace(/Ã©/g, 'é');
        c = c.replace(/Ã¨/g, 'è');
        c = c.replace(/Ã /g, 'à');
        c = c.replace(/Ãª/g, 'ê');
        c = c.replace(/ï¿½0tablissement/g, 'Établissement');
        c = c.replace(/0tablissement/g, 'Établissement');
        c = c.replace(/Logs d'activitÃ/g, 'Logs d\'activité');
        c = c.replace(/DÃ©connexion/g, 'Déconnexion');

        if (orig !== c) {
            fs.writeFileSync(p, c, 'utf8');
            console.log('Fixed up ' + p);
        }
    });
});