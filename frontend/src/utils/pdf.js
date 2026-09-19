import api from '../services/api';

/**
 * Downloads a PDF file from an authenticated API endpoint.
 *
 * @param {string} endpoint - API path (e.g. '/pdf/decisions/{id}')
 * @param {string} defaultFilename - Default filename for the downloaded PDF
 */
export async function downloadPdf(endpoint, defaultFilename = 'dokumen-sikomando.pdf') {
  try {
    const res = await api.get(endpoint, {
      responseType: 'blob',
    });

    const blob = new Blob([res.data || res], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = defaultFilename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => window.URL.revokeObjectURL(url), 2000);
  } catch (err) {
    console.error('Failed to download PDF:', err);
    throw err;
  }
}

/**
 * Opens a PDF document from an authenticated API endpoint in a new browser tab.
 *
 * @param {string} endpoint - API path (e.g. '/pdf/decisions/{id}')
 */
export async function openPdf(endpoint) {
  try {
    const res = await api.get(endpoint, {
      responseType: 'blob',
    });

    const blob = new Blob([res.data || res], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    window.open(url, '_blank');
    setTimeout(() => window.URL.revokeObjectURL(url), 30000);
  } catch (err) {
    console.error('Failed to open PDF:', err);
    throw err;
  }
}

