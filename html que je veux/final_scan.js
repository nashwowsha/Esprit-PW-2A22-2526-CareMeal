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
    let m = c.match(/[^\x00-\x7F]/g);
    if(m) {
        let allowed = '—éàèÉùçâîôöïëüœŒ€’«»“”‘ "\n\r\tº°₁₂₃₄₅₆₇₈₉₀⁺⁻⁼⁽⁾ⁿ₊₋₌₍₎‰•▾▸→←↑↓−×÷±=≠⁄–\uFEFF\u200B\u200C\u200D\u2060\u2028\u2029\uFE0E\uFE0F¸¹©„‐‑';
        let unique = [...new Set(m)].filter(x => !allowed.includes(x));
        if (unique.length > 0) {
            console.log(f, '->', JSON.stringify(unique));
        }
    }
});