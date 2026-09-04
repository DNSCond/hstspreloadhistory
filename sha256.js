// textor
const encoder = new TextEncoder;

async function sha256(strx) {
    const buffer = encoder.encode(await strx);
    return crypto.subtle.digest('SHA-256', buffer)
    .then(buf => new Uint8Array(buf).toBase64());
}
