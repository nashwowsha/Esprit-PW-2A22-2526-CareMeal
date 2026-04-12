const fs = require('fs');
const path = require('path');
function walk(d) {
    let r=[];
    fs.readdirSync(d).forEach(f => {
        let p=path.join(d,f);
        if(fs.statSync(p).isDirectory() && !p.includes('node_modules') && !p.includes('.git')){r=r.concat(walk(p));}
        else if(p.endsWith('.html') || p.endsWith('.js') || p.endsWith('.css')){r.push(p);}
    });
    return r;
}
const files = walk(__dirname);

files.forEach(f => {
    let c = fs.readFileSync(f, 'utf8');
    let m = c.match(/[\u2000-\u3300]/g);
    if(m) {
        let unique = [...new Set(m)];
        let ignore = ['—','’','»','«','”','“','‘','\u2028','\u200B','\u200D', '…','€','‰','•','▾','▸','→','←','↑','↓','−','×','÷','±','=','≠','⁄','–','¹','₂','„'];
        let actual = unique.filter(x => !ignore.includes(x));
        if (actual.length > 0) {
            console.log(f, '->', JSON.stringify(actual));
        }
    }
});
