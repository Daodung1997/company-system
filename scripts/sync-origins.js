const fs = require('fs');
const path = require('path');

const MAPPINGS = [
    {
        source: path.join(__dirname, '../docs/origins/admin'),
        target: path.join(__dirname, '../docs/origins/admin.md'),
        title: 'ADMIN SPECIFICATION'
    },
    {
        source: path.join(__dirname, '../docs/origins/app'),
        target: path.join(__dirname, '../docs/origins/user.md'),
        title: 'USER SPECIFICATION (CUSTOMER - WORKER)'
    }
];

function getMarkdownFiles(dir, baseDir = dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    
    // Sort to maintain consistency
    list.sort((a, b) => {
        // Prioritize files over directories or vice versa? 
        // Usually alphabetical is fine.
        return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
    });

    list.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);
        
        if (stat && stat.isDirectory()) {
            results = results.concat(getMarkdownFiles(filePath, baseDir));
        } else if (file.endsWith('.md')) {
            results.push(filePath);
        }
    });
    return results;
}

function sync() {
    console.log('🚀 Starting synchronization of documentation files...');

    MAPPINGS.forEach(({ source, target, title }) => {
        if (!fs.existsSync(source)) {
            console.warn(`⚠️ Source directory not found: ${source}`);
            return;
        }

        const files = getMarkdownFiles(source);
        let combinedContent = `# ${title}\n\n`;
        
        // Generate a simple Table of Contents at the top
        combinedContent += `## TABLE OF CONTENTS\n\n`;
        files.forEach(file => {
            const relPath = path.relative(source, file);
            const anchor = relPath.toLowerCase().replace(/[^a-z0-9]+/g, '-');
            combinedContent += `- [${relPath}](#${anchor})\n`;
        });
        combinedContent += `\n---\n\n`;

        files.forEach(file => {
            const relPath = path.relative(source, file);
            const content = fs.readFileSync(file, 'utf8');
            const anchor = relPath.toLowerCase().replace(/[^a-z0-9]+/g, '-');
            
            combinedContent += `<a name="${anchor}"></a>\n`;
            combinedContent += `# FILE: ${relPath}\n\n`;
            combinedContent += content;
            combinedContent += `\n\n---\n\n`;
        });

        fs.writeFileSync(target, combinedContent, 'utf8');
        console.log(`✅ Synchronized ${files.length} files to ${path.basename(target)}`);
    });

    console.log('✨ All documentation synced!');
}

sync();
