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

    // making our own hasCursor method for checking a cursor is added or not
    AceMultiCursorManager.prototype.hasCursor = function(cursorId){
        return this._cursors.hasOwnProperty(cursorId)
    }
    export default {
        props: [
            'user'
        ],
        mounted() {
            const channel = window.Echo.join('editor').error((error) => { console.log(error) });
            const editor = ace.edit('editor');
            const session = editor.getSession();
            const doc = session.getDocument();
            const selection = session.getSelection();
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

            selection.on('changeCursor', () => {
                channel.whisper('change-cursor', {
                    position: editor.getCursorPosition(),
                    userId: this.user.id,
                })
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

            channel.listenForWhisper('change-cursor', ({position, userId}) => {
                if(cursorManager.hasCursor(`cursor-${userId}`)){
                    cursorManager.setCursor(`cursor-${userId}`, position);
                }
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
