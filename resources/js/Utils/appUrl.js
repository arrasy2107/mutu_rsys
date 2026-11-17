export function appUrl(path, basePath = import.meta.env.BASE_URL) {
    const normalizedBasePath = basePath.endsWith('/')
        ? basePath
        : `${basePath}/`;
    const normalizedPath = String(path).replace(/^\/+/, '');

    return `${normalizedBasePath}${normalizedPath}`;
}
