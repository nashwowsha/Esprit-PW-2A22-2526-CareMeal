const fs = require('fs');
const path = require('path');
function walk(d) {
    let r=[];
    fs.readdirSync(d).forEach(f => {
        let p=path.join(d,f);
        if(fs.statSync(p).isDirectory() && !p.includes('node_modules') && !p.includes('.git')){r=r.concat(walk(p));}
        else if(p.endsWith('.css')){r.push(p);}
    });
    return r;
}
walk(__dirname).forEach(f => {
    let lines = fs.readFileSync(f, 'utf8').split('\n');
    lines.forEach((l, i) => {
        if(l.includes('content:')) {
            console.log(f + ':' + i + ': ' + l.trim());
        }
    });
});
