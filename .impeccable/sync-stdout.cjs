const fs = require('node:fs');
const util = require('node:util');

console.log = (...values) => {
    fs.writeSync(1, `${util.format(...values)}\n`);
};
