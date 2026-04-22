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
walk(__dirname).forEach(f => {
    let c = fs.readFileSync(f, 'utf8');
    let m1 = c.match(/placeholder=[\"\'][^\"\']*<i class/gi);
    let m2 = c.match(/value=[\"\'][^\"\']*<i class/gi);
    if(m1 || m2) {
        console.log(f, '->', m1, m2);
    }
});