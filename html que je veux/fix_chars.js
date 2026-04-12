const fs = require('fs');

function cleanFile(p) {
    let c = fs.readFileSync(p, 'utf8');
    let orig = c;
    
    // HTML / Text Fixes
    c = c.replace(/ï¿½<!DOCTYPE html>/g, '<!DOCTYPE html>');
    c = c.replace(/ï¿½Ã‰tablissement/g, 'Établissement');
    c = c.replace(/Mon ï¿½Ã‰tablissement/g, 'Mon Établissement');
    c = c.replace(/Mon Ã‰tablissement/g, 'Mon Établissement');
    c = c.replace(/ï¿½tablissement/g, 'Établissement');
    c = c.replace(/>ï¿½</g, '>—<'); // UI dash
    c = c.replace(/'ï¿½'/g, '\'—\''); // JS dash
    c = c.replace(/COï¿½/g, 'CO₂');
    c = c.replace(/ï¿½/g, '—'); // Catch-all for remaining garbage that represents a dash
    
    if (orig !== c) {
        fs.writeFileSync(p, c, 'utf8');
        console.log('Fixed more ' + p);
    }
}

['admin','student','partner','js'].forEach(d => {
    if (!fs.existsSync(d)) return;
    fs.readdirSync(d).filter(f => f.endsWith('.html') || f.endsWith('.js')).forEach(f => {
        cleanFile(d + '/' + f);
    });
});
