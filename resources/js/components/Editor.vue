<template>
    <div>
        <h3>Active User(s) - ({{ users.length }})</h3>
        <ul>
            <li v-for="user in users">{{ user.name }}</li>
        </ul>
        <div id="editor">console.log('hellow world');</div>
    </div>

</template>

<script>
    import ace from 'ace-builds';
    import { AceMultiCursorManager, AceMultiSelectionManager } from '@convergencelabs/ace-collab-ext';
    import 'ace-builds/webpack-resolver';
    import 'ace-builds/src-noconflict/mode-javascript';
    import '@convergencelabs/ace-collab-ext/dist/css/ace-collab-ext.min.css'

    // making our own hasCursor method for checking a cursor is added or not
    AceMultiCursorManager.prototype.hasCursor = function(cursorId){
        return this._cursors.hasOwnProperty(cursorId)
    }

    // making our own hasSelection method for checking a text is full selected is added or not
    AceMultiSelectionManager.prototype.hasSelection = function(selectionId){
        return this._selections.hasOwnProperty(selectionId);
    }
    export default {
        props: [
            'user'
        ],
        data() {
          return {
              users: [],
          }
        },
        mounted() {
            const channel = window.Echo.join('editor').here( (users) => {
                this.users = users.filter((user) => { // listing all active users.
                    return user.id != this.user.id;
                })
            }).joining((user) => { // adding joined user to users list
                this.users.push(user)
            }).leaving((user) => { // kicking out the logout users from users list so that active users list are consistance
                for(const [index, u] of this.users.entries()){
                    if(u.id === user.id){
                        this.users.splice(index, 1);
                        break;
                    }
                }
            }).error((error) => { console.log(error) });
            const editor = ace.edit('editor');
            const session = editor.getSession();
            const doc = session.getDocument();
            const selection = session.getSelection();
            const cursorManager = new AceMultiCursorManager(session);
            const selectionManager = new AceMultiSelectionManager(session);

            const { Range } = ace.require('ace/range');
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

            selection.on('changeSelection', () => {
                channel.whisper('change-selection', {
                    user: {
                        id: this.user.id,
                        name: this.user.name,
                    },
                    ranges: selection.getAllRanges(),

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

            channel.listenForWhisper('change-selection', ({user, ranges}) => {
                ranges = ranges.map( (range) => {
                    return new Range(
                        range.start.row,
                        range.start.column,
                        range.end.row,
                        range.end.column,
                    )
                })
                if(selectionManager.hasSelection(`selection-${user.id}`)){
                    selectionManager.setSelection(`selection-${user.id}`, ranges);
                } else {
                    selectionManager.addSelection(`selection-${user.id}`, user.name,'green',ranges);
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
