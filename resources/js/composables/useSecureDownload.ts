import axios from 'axios';

export function useSecureDownload() {
  const downloadFile = async (url: string, filename: string, data?: any) => {
    try {
      const response = await axios({
        method: data ? 'POST' : 'GET',
        url,
        data,
        responseType: 'blob',
        headers: {
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        },
        timeout: 30000,
      });

      // Create blob with proper MIME type
      const blob = new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });

      // Use a more secure approach for file download
      if (window.navigator && (window.navigator as any).msSaveOrOpenBlob) {
        // For IE
        (window.navigator as any).msSaveOrOpenBlob(blob, filename);
        return true;
      } else {
        // For modern browsers - use a more secure approach
        const downloadUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = filename;
        link.style.display = 'none';
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener noreferrer');

        // Add to DOM, click, and remove immediately
        document.body.appendChild(link);
        link.click();

        // Clean up immediately to prevent security warnings
        requestAnimationFrame(() => {
          if (document.body.contains(link)) {
            document.body.removeChild(link);
          }
          window.URL.revokeObjectURL(downloadUrl);
        });

        return true;
      }
    } catch (error: any) {
      console.error('Download error:', error);
      throw error;
    }
  };

  return {
    downloadFile
  };
}
