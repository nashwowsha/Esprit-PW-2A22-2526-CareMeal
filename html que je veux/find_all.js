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
let filesWithEmoji = {};

const emojiMap = {
    '<i class="fa-solid fa-hourglass-half"></i>': '<i class="fa-solid fa-hourglass-half"></i>',
    '<i class="fa-solid fa-star"></i>': '<i class="fa-solid fa-star"></i>',
    '<i class="fa-solid fa-clock"></i>': '<i class="fa-solid fa-clock"></i>',
    // also other missing emojis we might find.
};

files.forEach(f => {
    let content = fs.readFileSync(f, 'utf8');
    const orig = content;
    
    // First, let's find all emojis
    const emojis = content.match(/\p{Emoji_Presentation}/gu);
    if (emojis) {
        emojis.forEach(e => {
            allEmojis.add(e);
            if (!filesWithEmoji[e]) filesWithEmoji[e] = [];
            if (!filesWithEmoji[e].includes(f)) filesWithEmoji[e].push(f);
        });
    }

    // Attempt replacing the missing ones we already know:
    for (const [emoji, faClass] of Object.entries(emojiMap)) {
        content = content.split(emoji).join(faClass);
    }
    
    // Also `<i class="fa-solid fa-star"></i>` might be another variant `<i class="fa-solid fa-star"></i>` vs `<i class="fa-solid fa-star"></i>`
    content = content.split('<i class="fa-solid fa-star"></i>').join('<i class="fa-solid fa-star"></i>');

    if (content !== orig) {
        fs.writeFileSync(f, content, 'utf8');
        console.log('Fixed emojis in ' + f);
    }
});

console.log('Found Emoji_Presentation:', Array.from(allEmojis));
console.log('They were in:', filesWithEmoji);
