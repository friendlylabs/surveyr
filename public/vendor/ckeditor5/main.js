import {
	BalloonEditor,
	AutoLink,
	Autosave,
	BlockToolbar,
	Bold,
	Essentials,
	Italic,
	Link,
	List,
	ListProperties,
	PageBreak,
	Paragraph,
	Title,
	Markdown
} from 'ckeditor5';

/**
 * Create a free account with a trial: https://portal.ckeditor.com/checkout?plan=free
 */
const LICENSE_KEY = 'GPL'; // or <YOUR_LICENSE_KEY>.

const editorConfig = {
	toolbar: {
		items: ['bold', 'italic', '|', 'pageBreak', 'link', '|', 'bulletedList', 'numberedList'],
		shouldNotGroupWhenFull: false
	},
	plugins: [AutoLink, Autosave, BlockToolbar, Bold, Essentials, Italic, Link, List, ListProperties, PageBreak, Paragraph, Title, Markdown],
	blockToolbar: ['bold', 'italic', '|', 'link', '|', 'bulletedList', 'numberedList'],
	initialData:initialData,	
	licenseKey: LICENSE_KEY,
	link: {
		addTargetToExternalLinks: true,
		defaultProtocol: 'https://',
		decorators: {
			toggleDownloadable: {
				mode: 'manual',
				label: 'Downloadable',
				attributes: {
					download: 'file'
				}
			}
		}
	},
	list: {
		properties: {
			styles: true,
			startIndex: true,
			reversed: true
		}
	},
	placeholder: 'Type or paste your content here!'
};

// BalloonEditor.create(document.querySelector('#formDescription'), editorConfig);
//let editorInstance;
// BalloonEditor.create(document.querySelector('#editor'), editorConfig).then(editor => {
//	editorInstance = editor;
//	window.editor = editor;
// }) .catch(error => {
//	console.error(error);
// });

window.editorConfigs = editorConfig;
window.BalloonEditor = BalloonEditor;