// Trusted container test driver. Expectations stay in this process.
import { gradeInSandbox } from '../src/server.js';
let raw = '';
for await (const chunk of process.stdin) raw += chunk;
process.stdout.write(JSON.stringify(await gradeInSandbox(JSON.parse(raw))));
