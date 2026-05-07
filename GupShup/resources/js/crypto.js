/**
 * GupShup E2EE Crypto Module
 * Uses Web Crypto API for end-to-end encryption.
 * - ECDH P-256 for key agreement
 * - HKDF for key derivation
 * - AES-GCM-256 for message encryption
 */

const STORAGE_KEY = 'gupshup_private_key';
const ECDH_PARAMS = { name: 'ECDH', namedCurve: 'P-256' };

/**
 * Generate an ECDH key pair for the current user.
 * @returns {Promise<CryptoKeyPair>}
 */
export async function generateKeyPair() {
    return await window.crypto.subtle.generateKey(
        ECDH_PARAMS,
        true, // extractable (so we can export/import)
        ['deriveKey', 'deriveBits']
    );
}

/**
 * Export a public key as JWK.
 * @param {CryptoKey} publicKey
 * @returns {Promise<JsonWebKey>}
 */
export async function exportPublicKey(publicKey) {
    return await window.crypto.subtle.exportKey('jwk', publicKey);
}

/**
 * Import a public key from JWK.
 * @param {JsonWebKey|string} jwk
 * @returns {Promise<CryptoKey>}
 */
export async function importPublicKey(jwk) {
    if (typeof jwk === 'string') {
        jwk = JSON.parse(jwk);
    }
    return await window.crypto.subtle.importKey(
        'jwk',
        jwk,
        ECDH_PARAMS,
        true,
        [] // public key doesn't need usage
    );
}

/**
 * Export a private key as JWK.
 * @param {CryptoKey} privateKey
 * @returns {Promise<JsonWebKey>}
 */
export async function exportPrivateKey(privateKey) {
    return await window.crypto.subtle.exportKey('jwk', privateKey);
}

/**
 * Import a private key from JWK.
 * @param {JsonWebKey|string} jwk
 * @returns {Promise<CryptoKey>}
 */
export async function importPrivateKey(jwk) {
    if (typeof jwk === 'string') {
        jwk = JSON.parse(jwk);
    }
    return await window.crypto.subtle.importKey(
        'jwk',
        jwk,
        ECDH_PARAMS,
        true,
        ['deriveKey', 'deriveBits']
    );
}

/**
 * Derive a shared AES-GCM-256 key from ECDH private + public keys.
 * @param {CryptoKey} privateKey - Our private key
 * @param {CryptoKey} publicKey - Recipient's public key
 * @returns {Promise<CryptoKey>}
 */
export async function deriveSharedSecret(privateKey, publicKey) {
    return await window.crypto.subtle.deriveKey(
        {
            name: 'ECDH',
            public: publicKey,
        },
        privateKey,
        {
            name: 'AES-GCM',
            length: 256,
        },
        false, // not extractable
        ['encrypt', 'decrypt']
    );
}

/**
 * Encrypt a message using AES-GCM.
 * @param {string} plaintext
 * @param {CryptoKey} sharedKey
 * @returns {Promise<{ciphertext: string, iv: string}>} Base64-encoded ciphertext and IV
 */
export async function encryptMessage(plaintext, sharedKey) {
    const encoder = new TextEncoder();
    const data = encoder.encode(plaintext);

    // Generate random IV (12 bytes for AES-GCM)
    const iv = window.crypto.getRandomValues(new Uint8Array(12));

    const encrypted = await window.crypto.subtle.encrypt(
        { name: 'AES-GCM', iv: iv },
        sharedKey,
        data
    );

    return {
        ciphertext: arrayBufferToBase64(encrypted),
        iv: arrayBufferToBase64(iv),
    };
}

/**
 * Decrypt a message using AES-GCM.
 * @param {string} ciphertextB64 - Base64-encoded ciphertext
 * @param {string} ivB64 - Base64-encoded IV
 * @param {CryptoKey} sharedKey
 * @returns {Promise<string>} Decrypted plaintext
 */
export async function decryptMessage(ciphertextB64, ivB64, sharedKey) {
    const ciphertext = base64ToArrayBuffer(ciphertextB64);
    const iv = base64ToArrayBuffer(ivB64);

    const decrypted = await window.crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: iv },
        sharedKey,
        ciphertext
    );

    const decoder = new TextDecoder();
    return decoder.decode(decrypted);
}

/**
 * Save the private key to localStorage.
 * @param {CryptoKey} privateKey
 */
export async function savePrivateKey(privateKey) {
    const jwk = await exportPrivateKey(privateKey);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(jwk));
}

/**
 * Load the private key from localStorage.
 * @returns {Promise<CryptoKey|null>}
 */
export async function loadPrivateKey() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (!stored) return null;

    try {
        const jwk = JSON.parse(stored);
        return await importPrivateKey(jwk);
    } catch (e) {
        console.error('Failed to load private key:', e);
        return null;
    }
}

/**
 * Check if a private key exists in localStorage.
 * @returns {boolean}
 */
export function hasPrivateKey() {
    return localStorage.getItem(STORAGE_KEY) !== null;
}

// --- Utility functions ---

function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
}

function base64ToArrayBuffer(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}
