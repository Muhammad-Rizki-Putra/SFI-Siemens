import * as pdfjsLib from 'pdfjs-dist/build/pdf';

pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdfjs/pdf.worker.min.mjs';

const config = window.__PDF_PREVIEW__ || {};
const url = config.fileUrl;

const canvas = document.getElementById('pdf-canvas');
const context = canvas?.getContext('2d');
const prevButton = document.getElementById('prev');
const nextButton = document.getElementById('next');
const pageInfo = document.getElementById('page-info');

let pdfDoc = null;
let pageNum = 1;
let rendering = false;
let pendingPage = null;

const updateButtons = () => {
    if (!pdfDoc || !prevButton || !nextButton) return;
    prevButton.disabled = pageNum <= 1;
    nextButton.disabled = pageNum >= pdfDoc.numPages;
};

const renderPage = (num) => {
    if (!pdfDoc || !canvas || !context) return;
    rendering = true;
    pdfDoc.getPage(num).then((page) => {
        const viewport = page.getViewport({ scale: 1.2 });
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const renderTask = page.render({ canvasContext: context, viewport });
        renderTask.promise.then(() => {
            rendering = false;
            if (pendingPage !== null) {
                const next = pendingPage;
                pendingPage = null;
                renderPage(next);
            }
        });

        if (pageInfo) {
            pageInfo.textContent = `Page ${num} of ${pdfDoc.numPages}`;
        }
        updateButtons();
    });
};

const queueRenderPage = (num) => {
    if (rendering) {
        pendingPage = num;
        return;
    }
    renderPage(num);
};

if (prevButton) {
    prevButton.addEventListener('click', () => {
        if (pageNum <= 1) return;
        pageNum -= 1;
        queueRenderPage(pageNum);
    });
}

if (nextButton) {
    nextButton.addEventListener('click', () => {
        if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
        pageNum += 1;
        queueRenderPage(pageNum);
    });
}

if (url) {
    pdfjsLib.getDocument(url).promise
        .then((pdf) => {
            pdfDoc = pdf;
            updateButtons();
            renderPage(pageNum);
        })
        .catch((error) => {
            if (pageInfo) {
                pageInfo.textContent = 'Unable to load PDF.';
            }
            console.error(error);
        });
}
