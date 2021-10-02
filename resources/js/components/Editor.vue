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
    import '@convergencelabs/ace-collab-ext/dist/css/ace-collab-ext.min.css'
    export default {
        props: [
            'user'
        ],
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

            editor.on('focus',() => {
                channel.whisper('add-cursor', {
                    user: {
                        id: this.user.id,
                        name: this.user.name,
                    },
                    position: editor.getCursorPosition()
                })
            });

            editor.on('blur', () => {
                channel.whisper('remove-cursor', this.user.id)
            })

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

            channel.listenForWhisper('add-cursor', ({user, position}) => {
                cursorManager.addCursor(`cursor-${user.id}`, user.name,'green',position);
            })

            channel.listenForWhisper('remove-cursor', (userId) => {
                cursorManager.removeCursor(`cursor-${userId}`);
            })
        }
    }
</script>

<style>
    #editor {
        height: 500px;
    }
    .remote-cursor .ace-multi-cursor-tooltip{
        opacity: 1 !important;
    }
</style>
