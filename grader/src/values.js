// Lossless tagged values. Limits reject outputs; they never change equality.
// Tags keep undefined/nonfinite numbers distinct from student JSON objects.
export function encode(value, depth = 0, ancestors = new Set()) {
    if (depth > 64) throw new Error('resource_limit');
    if (value === null) return ['null'];
    if (value === undefined) return ['undefined'];
    if (typeof value === 'number') return ['number', Number.isNaN(value) ? 'nan' : value === Infinity ? 'infinity' : value === -Infinity ? '-infinity' : Object.is(value, -0) ? '-0' : value];
    if (typeof value === 'string' || typeof value === 'boolean') return [typeof value, value];
    if (typeof value !== 'object') throw new Error('unsupported_value');
    if (ancestors.has(value)) throw new Error('unsupported_value');
    ancestors.add(value);
    try {
        if (Array.isArray(value)) return ['array', Array.from(value, item => encode(item, depth + 1, ancestors))];
        return ['object', Object.keys(value).sort().map(key => [key, encode(value[key], depth + 1, ancestors)])];
    } finally {
        ancestors.delete(value);
    }
}
