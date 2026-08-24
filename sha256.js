// textor
const encoder = new TextEncoder;

async function sha256(strx) {
    strx = `${await strx}`;
    if (strx?.isWellFormed?.()) {
        const buffer = encoder.encode(strx);
        return crypto.subtle.digest('SHA-256', buffer)
            .then(buf => new Uint8Array(buf).toBase64());
    }
    throw new TypeError;
}
