<template>
    <div id="editor">
        console.log('hellow world');
    </div>
</template>

<script>
    import ace from 'ace-builds';
    import 'ace-builds/webpack-resolver';
    import 'ace-builds/src-noconflict/mode-javascript';
    export default {
        mounted() {
            const editor = ace.edit('editor');
            const session = editor.getSession();
            const doc = session.getDocument();

            session.setMode('ace/mode/javascript');
            editor.setFontSize('22px');
        }
    }
</script>

<style>
    #editor {
        height: 500px;
    }
</style>
