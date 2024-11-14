class CacheService {
  constructor() {
    this.cache = new Map();
    this.defaultTTL = 3600; // 1 hour in seconds
  }

  set(key, value, ttl = this.defaultTTL) {
    const expiresAt = Date.now() + (ttl * 1000);
    this.cache.set(key, {
      value,
      expiresAt
    });
  }

  get(key) {
    const item = this.cache.get(key);
    if (!item) return null;

    if (Date.now() > item.expiresAt) {
      this.cache.delete(key);
      return null;
    }

    return item.value;
  }

  delete(key) {
    return this.cache.delete(key);
  }

  clear() {
    this.cache.clear();
  }

  // Clean expired items periodically
  startCleanup(interval = 300000) { // 5 minutes
    setInterval(() => {
      const now = Date.now();
      for (const [key, item] of this.cache.entries()) {
        if (now > item.expiresAt) {
          this.cache.delete(key);
        }
      }
    }, interval);
  }
}

export default new CacheService();