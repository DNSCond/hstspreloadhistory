// sha256
const decoder = new TextDecoder;

export class Base64Script extends HTMLScriptElement {
    parse() {
        const uint8array = Uint8Array.fromBase64(this.text);
        return decoder.decode(uint8array);
    }

    toJSON() {
        return this.parse();
    }

}

export class Base64OutputScript extends Base64Script {
    connectedCallback() {
        console.log(JSON.stringify(this, null, 2));
    }
}

customElements.define('b64-script', Base64Script, {extends: 'script'});
customElements.define('b64-log', Base64OutputScript, {extends: 'script'});
