<template>
    <div id="editor">
        console.log('hellow world');
    </div>
</template>

<script>
    import ace from 'ace-builds';
    import { AceMultiCursorManager } from '@convergencelabs/ace-collab-ext';
    import 'ace-builds/webpack-resolver';
    import 'ace-builds/src-noconflict/mode-javascript';
    import '@convergencelabs/ace-collab-ext/css/ace-collab-ext.css'
    export default {
        mounted() {
            const channel = window.Echo.join('editor').error((error) => { console.log(error) });
            const editor = ace.edit('editor');
            const session = editor.getSession();
            const doc = session.getDocument();

            const cursorManager = new AceMultiCursorManager(session);

            session.setMode('ace/mode/javascript');
            editor.setFontSize('22px');

            //Editor  events
            doc.on('change', (data) => {
                // check if user writing. if not then return. we are doing this check to avoid infinity call to this events.
                if(! (editor.curOp && editor.curOp.command.name)){
                    return;
                }
                channel.whisper('typing',data);
            });

            //WebSocket events
            channel.listenForWhisper('typing', (data) => {
                switch (data.action) {
                    case 'insert':
                        doc.insertMergedLines(data.start,data.lines);
                        break;
                    case 'remove':
                        doc.remove({
                            start: data.start,
                            end: data.end
                        });
                }

            })
        }
    }
</script>

<style>
    #editor {
        height: 500px;
    }
</style>
