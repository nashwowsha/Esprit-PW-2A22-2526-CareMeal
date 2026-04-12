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
let allEmojis = new Set();

files.forEach(f => {
    if (f.includes('find_all2.js') || f.includes('find_all.js')) return;
    let content = fs.readFileSync(f, 'utf8');
    
    // Find any character that isn't ASCII and isn't a common French letter
    const regex = /[^\x00-\x7FÀ-ÿœŒ€]/g;
    const matches = content.match(regex);
    if (matches) {
        matches.forEach(m => {
            // filter out typical punctuation we might have allowed:
            if (!['—', '’', '»', '«', '”', '“', '‘', ' ', '‐', '‑', '…'].includes(m)) {
                allEmojis.add(m);
            }
        });
    }
});
console.log('Non-ASCII/French chars:', Array.from(allEmojis));
