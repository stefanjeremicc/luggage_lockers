<template>
    <div v-if="editor" class="bg-[#111] border border-[#2A2A2A] rounded-lg overflow-hidden focus-within:border-[#F59E0B] transition">
        <!-- Toolbar -->
        <div class="flex items-center flex-wrap gap-0.5 px-2 py-1.5 border-b border-[#2A2A2A] bg-[#0F0F0F]">
            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="btn(editor.isActive('bold'))" title="Bold">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011-1h4a3.5 3.5 0 012.39 6.06A3.75 3.75 0 0112 16H7a1 1 0 01-1-1V4zm2 1v4h3a1.5 1.5 0 000-3H8zm0 6v4h4a1.75 1.75 0 000-3.5H8z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="btn(editor.isActive('italic'))" title="Italic">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8 3a1 1 0 000 2h1.5l-2.5 10H5a1 1 0 100 2h6a1 1 0 100-2h-1.5l2.5-10H14a1 1 0 100-2H8z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="btn(editor.isActive('underline'))" title="Underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 3v9a6 6 0 0012 0V3M4 21h16"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleStrike().run()" :class="btn(editor.isActive('strike'))" title="Strikethrough">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 12h16M10 4h4a4 4 0 014 4M10 20h4a4 4 0 004-4"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleHighlight().run()" :class="btn(editor.isActive('highlight'))" title="Highlight">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </button>

            <span class="w-px h-5 bg-[#2A2A2A] mx-1"></span>

            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="btn(editor.isActive('heading', { level: 2 }))" title="Heading 2">
                <span class="text-xs font-semibold">H2</span>
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="btn(editor.isActive('heading', { level: 3 }))" title="Heading 3">
                <span class="text-xs font-semibold">H3</span>
            </button>
            <button type="button" @click="editor.chain().focus().setParagraph().run()" :class="btn(editor.isActive('paragraph'))" title="Paragraph">
                <span class="text-xs">P</span>
            </button>

            <span class="w-px h-5 bg-[#2A2A2A] mx-1"></span>

            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="btn(editor.isActive('bulletList'))" title="Bullet list">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="4" cy="5" r="1.5"/><circle cx="4" cy="10" r="1.5"/><circle cx="4" cy="15" r="1.5"/><rect x="8" y="4" width="10" height="2"/><rect x="8" y="9" width="10" height="2"/><rect x="8" y="14" width="10" height="2"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="btn(editor.isActive('orderedList'))" title="Numbered list">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><text x="0" y="7" font-size="6" font-weight="bold">1.</text><text x="0" y="13" font-size="6" font-weight="bold">2.</text><text x="0" y="19" font-size="6" font-weight="bold">3.</text><rect x="8" y="4" width="10" height="2"/><rect x="8" y="9" width="10" height="2"/><rect x="8" y="14" width="10" height="2"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="btn(editor.isActive('blockquote'))" title="Quote">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5 7h3v3H5v3H2V7a3 3 0 013-3v3zm8 0h3v3h-3v3h-3V7a3 3 0 013-3v3z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()" :class="btn(editor.isActive('codeBlock'))" title="Code block">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            </button>

            <span class="w-px h-5 bg-[#2A2A2A] mx-1"></span>

            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="btn(editor.isActive({ textAlign: 'left' }))" title="Align left">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><rect x="2" y="4" width="16" height="2"/><rect x="2" y="8" width="10" height="2"/><rect x="2" y="12" width="16" height="2"/><rect x="2" y="16" width="10" height="2"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="btn(editor.isActive({ textAlign: 'center' }))" title="Align center">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><rect x="2" y="4" width="16" height="2"/><rect x="5" y="8" width="10" height="2"/><rect x="2" y="12" width="16" height="2"/><rect x="5" y="16" width="10" height="2"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="btn(editor.isActive({ textAlign: 'right' }))" title="Align right">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><rect x="2" y="4" width="16" height="2"/><rect x="8" y="8" width="10" height="2"/><rect x="2" y="12" width="16" height="2"/><rect x="8" y="16" width="10" height="2"/></svg>
            </button>

            <span class="w-px h-5 bg-[#2A2A2A] mx-1"></span>

            <button type="button" @click="setLink" :class="btn(editor.isActive('link'))" title="Link">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            </button>
            <button type="button" @click="promptImage" :class="btn(false)" title="Insert image">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </button>
            <button type="button" @click="promptYoutube" :class="btn(false)" title="Embed YouTube/Vimeo">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </button>
            <button type="button" @click="insertTable" :class="btn(false)" title="Insert table">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 10h18M3 14h18M9 6v12M15 6v12M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
            </button>
            <button type="button" @click="promptHtml" :class="btn(false)" title="Insert raw HTML">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 18l6-6-6-6M8 6l-6 6 6 6"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" :class="btn(false)" title="Horizontal rule">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><rect x="2" y="9" width="16" height="2"/></svg>
            </button>

            <span class="flex-1"></span>

            <button type="button" @click="toggleSource" :class="btn(showSource)" title="Toggle HTML source">
                <span class="text-[10px] font-mono font-bold">&lt;/&gt;</span>
            </button>
            <button type="button" @click="editor.chain().focus().undo().run()" :class="btn(false)" :disabled="!editor.can().undo()" title="Undo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 10h10a5 5 0 110 10h-3m-7-10l4-4m-4 4l4 4"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().redo().run()" :class="btn(false)" :disabled="!editor.can().redo()" title="Redo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 10H11a5 5 0 100 10h3m7-10l-4-4m4 4l-4 4"/></svg>
            </button>
        </div>

        <!-- Table actions row (visible only inside a table) -->
        <div v-if="editor.isActive('table')" class="flex items-center flex-wrap gap-0.5 px-2 py-1 border-b border-[#2A2A2A] bg-[#0A0A0A]">
            <button type="button" @click="editor.chain().focus().addColumnBefore().run()" :class="btn(false)" class="text-xs">+ col before</button>
            <button type="button" @click="editor.chain().focus().addColumnAfter().run()" :class="btn(false)" class="text-xs">+ col after</button>
            <button type="button" @click="editor.chain().focus().deleteColumn().run()" :class="btn(false)" class="text-xs">− col</button>
            <span class="w-px h-4 bg-[#2A2A2A] mx-1"></span>
            <button type="button" @click="editor.chain().focus().addRowBefore().run()" :class="btn(false)" class="text-xs">+ row before</button>
            <button type="button" @click="editor.chain().focus().addRowAfter().run()" :class="btn(false)" class="text-xs">+ row after</button>
            <button type="button" @click="editor.chain().focus().deleteRow().run()" :class="btn(false)" class="text-xs">− row</button>
            <span class="w-px h-4 bg-[#2A2A2A] mx-1"></span>
            <button type="button" @click="editor.chain().focus().toggleHeaderRow().run()" :class="btn(false)" class="text-xs">header</button>
            <button type="button" @click="editor.chain().focus().mergeOrSplit().run()" :class="btn(false)" class="text-xs">merge/split</button>
            <button type="button" @click="editor.chain().focus().deleteTable().run()" :class="btn(false)" class="text-xs text-[#EF4444]">delete table</button>
        </div>

        <!-- Editor content / source -->
        <editor-content v-show="!showSource" :editor="editor" class="rich-editor-content px-4 py-3 text-white" />
        <textarea v-if="showSource" v-model="sourceHtml" @blur="applySource"
            class="w-full bg-[#0A0A0A] text-white font-mono text-sm px-4 py-3 focus:outline-none"
            :style="{ minHeight: minHeight }" spellcheck="false"></textarea>
    </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, onMounted } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import Youtube from '@tiptap/extension-youtube';
import Highlight from '@tiptap/extension-highlight';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Start writing…' },
    minHeight: { type: String, default: '240px' },
});
const emit = defineEmits(['update:modelValue']);

const showSource = ref(false);
const sourceHtml = ref('');

const editor = new Editor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [2, 3, 4] } }),
        Underline,
        Highlight.configure({ HTMLAttributes: { class: 'bg-[#F59E0B]/30 text-[#F59E0B] px-0.5 rounded' } }),
        Link.configure({ openOnClick: false, autolink: true, HTMLAttributes: { class: 'text-[#F59E0B] underline', rel: 'noopener noreferrer', target: '_blank' } }),
        Image.configure({ HTMLAttributes: { class: 'rounded-lg' } }),
        Youtube.configure({ HTMLAttributes: { class: 'rounded-lg w-full aspect-video' }, controls: true, nocookie: true, width: 640, height: 360 }),
        Placeholder.configure({ placeholder: props.placeholder }),
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Table.configure({ resizable: true, HTMLAttributes: { class: 'rich-table' } }),
        TableRow,
        TableHeader,
        TableCell,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-invert max-w-none focus:outline-none',
            style: `min-height:${props.minHeight}`,
        },
    },
    onUpdate: ({ editor: e }) => {
        emit('update:modelValue', e.getHTML());
    },
});

watch(() => props.modelValue, (v) => {
    if (showSource.value) return;
    if (v !== editor.getHTML()) editor.commands.setContent(v || '', false);
});

onBeforeUnmount(() => editor.destroy());

const btn = (active) =>
    'p-1.5 rounded hover:bg-[#1A1A1A] transition disabled:opacity-40 disabled:hover:bg-transparent ' +
    (active ? 'bg-[#F59E0B]/15 text-[#F59E0B]' : 'text-[#A0A0A0]');

const setLink = () => {
    const prev = editor.getAttributes('link').href;
    const url = window.prompt('URL', prev || 'https://');
    if (url === null) return;
    if (url === '') {
        editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

const promptImage = () => {
    const url = window.prompt('Image URL (or paste full <img> tag)');
    if (!url) return;
    editor.chain().focus().setImage({ src: url }).run();
};

const promptYoutube = () => {
    const url = window.prompt('YouTube or Vimeo URL');
    if (!url) return;
    editor.chain().focus().setYoutubeVideo({ src: url }).run();
};

const insertTable = () => {
    editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
};

const promptHtml = () => {
    const html = window.prompt('Paste HTML to insert (e.g. <iframe>, custom embed)');
    if (!html) return;
    editor.chain().focus().insertContent(html).run();
};

const toggleSource = () => {
    if (!showSource.value) {
        sourceHtml.value = editor.getHTML();
        showSource.value = true;
    } else {
        applySource();
        showSource.value = false;
    }
};

const applySource = () => {
    if (!showSource.value) return;
    editor.commands.setContent(sourceHtml.value || '', false);
    emit('update:modelValue', editor.getHTML());
};
</script>

<style>
.rich-editor-content .ProseMirror {
    min-height: 240px;
    outline: none;
}
.rich-editor-content .ProseMirror p.is-editor-empty:first-child::before {
    color: #4B5563;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}
.rich-editor-content .ProseMirror h2 { font-size: 1.4rem; font-weight: 700; margin: 1rem 0 0.5rem; }
.rich-editor-content .ProseMirror h3 { font-size: 1.15rem; font-weight: 600; margin: 0.8rem 0 0.4rem; }
.rich-editor-content .ProseMirror p { margin: 0.4rem 0; line-height: 1.6; }
.rich-editor-content .ProseMirror ul { list-style: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
.rich-editor-content .ProseMirror ol { list-style: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
.rich-editor-content .ProseMirror blockquote { border-left: 3px solid #F59E0B; padding-left: 1rem; color: #A0A0A0; margin: 0.8rem 0; font-style: italic; }
.rich-editor-content .ProseMirror hr { border-color: #2A2A2A; margin: 1rem 0; }
.rich-editor-content .ProseMirror img { max-width: 100%; border-radius: 0.5rem; margin: 0.5rem 0; }
.rich-editor-content .ProseMirror a { color: #F59E0B; text-decoration: underline; }
.rich-editor-content .ProseMirror pre { background: #0A0A0A; border: 1px solid #2A2A2A; border-radius: 0.5rem; padding: 0.75rem 1rem; overflow-x: auto; font-family: ui-monospace, monospace; font-size: 0.85rem; margin: 0.75rem 0; }
.rich-editor-content .ProseMirror code { background: #2A2A2A; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.85em; }
.rich-editor-content .ProseMirror pre code { background: transparent; padding: 0; }
.rich-editor-content .ProseMirror iframe { width: 100%; aspect-ratio: 16/9; border-radius: 0.5rem; margin: 0.75rem 0; border: 0; }
.rich-editor-content .ProseMirror .rich-table,
.rich-editor-content .ProseMirror table {
    border-collapse: collapse;
    width: 100%;
    margin: 0.75rem 0;
    table-layout: fixed;
    overflow: hidden;
}
.rich-editor-content .ProseMirror table td,
.rich-editor-content .ProseMirror table th {
    border: 1px solid #2A2A2A;
    padding: 0.5rem 0.75rem;
    vertical-align: top;
    position: relative;
    min-width: 1em;
}
.rich-editor-content .ProseMirror table th { background: #1A1A1A; font-weight: 600; }
.rich-editor-content .ProseMirror table .selectedCell:after {
    content: ''; position: absolute; inset: 0;
    background: rgba(245, 158, 11, 0.15); pointer-events: none;
}
.rich-editor-content .ProseMirror table .column-resize-handle {
    position: absolute; right: -2px; top: 0; bottom: 0; width: 4px;
    background: #F59E0B; pointer-events: none;
}
.rich-editor-content .ProseMirror.resize-cursor { cursor: col-resize; }
</style>
