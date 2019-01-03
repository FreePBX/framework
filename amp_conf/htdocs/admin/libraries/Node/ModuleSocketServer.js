/**
 * TODO: use ampconf value for fwconsole path
 * TODO: make the websocket port configurable 
 * TODO: Make this launch at runtime and die when done. 
 */
const FreePBX = require('freepbx');
const WebSocket = require('ws');
const util = require('util');

const wsconf = {
    port: 8080,
    perMessageDeflate: {
        zlibDeflateOptions: {
            // See zlib defaults.
            chunkSize: 1024,
            memLevel: 7,
            level: 3
        },
        zlibInflateOptions: {
            chunkSize: 10 * 1024
        },
        // Other options settable:
        clientNoContextTakeover: true, // Defaults to negotiated value.
        serverNoContextTakeover: true, // Defaults to negotiated value.
        serverMaxWindowBits: 10, // Defaults to negotiated value.
        // Below options specified as default values.
        concurrencyLimit: 10, // Limits zlib concurrency for perf.
        threshold: 1024 // Size (in bytes) below which messages
        // should not be compressed.
    }
}

const wss = new WebSocket.Server(wsconf);
const spawn = require('child_process').spawn;
wss.on('connection', function connection(ws) {
    ws.on('message', function incoming(message) {
        console.log(JSON.stringify(message));
        if(!message.session){
            ws.send(`{status:"error", message: "No session data received"}`);
            return;
        }
        var session = getSession(message.session);
        if(!session.ampuser.username){
            ws.send(`{status:"error", message: "Check that your session is not expired."}`);
            return;
        }     
        var data = {};
        try{
            var data = JSON.parse(message);
            console.log(data);
        } catch(e){
        }
        var args = ['ma'];
        switch (data.action) {
            case "install":
                args.push('install');
                data.packages.forEach(element => {
                   if(element.version){
                       args.push(`${element.rawname}:${element.version}`);
                   }else{
                       args.push(`${element.rawname}`);
                   }
                });
                break;
            case "uninstall":
                args.push('uninstall');
                data.packages.forEach(element => {
                    if (element.version) {
                        args.push(`${element.rawname}:${element.version}`);
                    } else {
                        args.push(`${element.rawname}`);
                    }
                });
            break;
            case "update":
                args.push('update');
                data.packages.forEach(element => {
                    if (element.version) {
                        args.push(`${element.rawname}:${element.version}`);
                    } else {
                        args.push(`${element.rawname}`);
                    }
                });
            break;
            case "enable":
                args.push('enable');
                data.packages.forEach(element => {
                    if (element.version) {
                        args.push(`${element.rawname}:${element.version}`);
                    } else {
                        args.push(`${element.rawname}`);
                    }
                });
            break;
            case "disable":
                args.push('disable');
                data.packages.forEach(element => {
                    if (element.version) {
                        args.push(`${element.rawname}:${element.version}`);
                    } else {
                        args.push(`${element.rawname}`);
                    }
                });
            break;
            default:
                ws.send('{status: "error", message: "Information sent to the server is incorrect"}');
            return;
        }
        const ls = spawn('fwconsole', args);
        ls.stdout.on('data', (out) => {
            console.log(`stdout: ${out}`);
            ws.send(`{status: "progress", message: "${out}"}`);
        });

        ls.stderr.on('data', (out) => {
            ws.send(`{status:"error", message: "${out}"}`);
        });

        ls.on('close', (code) => {
            ws.send(`{status:"finished", return: "${code}"}`);
        });
    });
});

function getSession(id){
    var out = {};
    return FreePBX.connect()
    .then(function(pbx){
        return pbx.kvstore.getConfig('FreePBX', id, 'sessions');
    });
}