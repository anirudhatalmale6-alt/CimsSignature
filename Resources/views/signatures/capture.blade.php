@extends('smartdash::layouts.default')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Capture Signature</h4>
                    <a href="{{ route('signatures.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <!-- Status Messages -->
                    <div id="statusMessage" class="alert d-none"></div>

                    <!-- Client Details Form -->
                    <form id="signatureForm">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="client_name" name="client_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="client_reference" class="form-label">Client Reference</label>
                                    <input type="text" class="form-control" id="client_reference" name="client_reference" placeholder="e.g., CLT001">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID Number</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purpose" class="form-label">Purpose / Document</label>
                                    <input type="text" class="form-control" id="purpose" name="purpose" placeholder="e.g., SARS Representative Appointment">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="document_reference" class="form-label">Document Reference</label>
                                    <input type="text" class="form-control" id="document_reference" name="document_reference">
                                </div>
                            </div>
                        </div>

                        <!-- Signature Capture Area -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Signature</h5>
                                        <span id="deviceStatus" class="badge bg-secondary">Checking connection...</span>
                                    </div>
                                    <div class="card-body text-center">
                                        <!-- Signature Display Box -->
                                        <div id="signatureBox" style="border: 2px dashed #ccc; min-height: 150px; background: #fff; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; position: relative;">
                                            <div id="signaturePlaceholder">
                                                <i class="fa fa-pen fa-3x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Click "Capture Signature" to sign on Wacom device</p>
                                            </div>
                                            <img id="signatureImage" src="" alt="Signature" style="max-width: 100%; max-height: 140px; display: none;">
                                        </div>

                                        <!-- Hidden field to store signature data -->
                                        <input type="hidden" id="signature_data" name="signature_data">

                                        <!-- Buttons -->
                                        <div class="btn-group mb-3">
                                            <button type="button" id="btnCapture" class="btn btn-primary btn-lg">
                                                <i class="fa fa-pen"></i> Capture Signature
                                            </button>
                                            <button type="button" id="btnClear" class="btn btn-warning btn-lg" disabled>
                                                <i class="fa fa-eraser"></i> Clear
                                            </button>
                                            <button type="submit" id="btnSave" class="btn btn-success btn-lg" disabled>
                                                <i class="fa fa-save"></i> Save Signature
                                            </button>
                                        </div>

                                        <div class="text-muted small">
                                            <p class="mb-1"><strong>Requirements:</strong> Wacom STU SigCaptX service must be running on your computer.</p>
                                            <p class="mb-0">Open browser console (F12) to see connection status.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fallback Canvas -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Alternative: Draw with Mouse/Touch</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="fallbackCanvas" width="500" height="150" style="border: 1px solid #000; background: #fff; cursor: crosshair;"></canvas>
                                        <div class="mt-2">
                                            <button type="button" id="btnUseFallback" class="btn btn-info btn-sm">
                                                <i class="fa fa-check"></i> Use This Signature
                                            </button>
                                            <button type="button" id="btnClearFallback" class="btn btn-secondary btn-sm">
                                                <i class="fa fa-eraser"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal window container for STU display mirror -->
<div id="modal-background" class="modal-background" style="display: none;"></div>
<div id="signatureWindow" class="signature-window" style="display: none;">
    <canvas id="stuCanvas"></canvas>
</div>

<style>
.modal-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
}
.signature-window {
    position: fixed;
    z-index: 9999;
    background: #fff;
    border: 2px solid #333;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
</style>

<!-- Wacom STU SigCaptX SDK - Official Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/q.js/1.5.1/q.min.js"></script>
<script>
// ============================================================================
// Wacom STU SigCaptX SDK - wgssStuSdk.js (embedded for convenience)
// Based on official Wacom SDK from https://github.com/Wacom-Developer/stu-sdk-sigcaptx-samples
// ============================================================================

var WacomGSS = WacomGSS || {};

WacomGSS.STUConstructor = (function() {
    var websocket;
    var MaxChunkSize = 65535;
    var ticketCount = 0;
    var streamCount = 0;
    var table = {};
    var stream = {};

    function checkExists(obj) {
        return 'undefined' !== typeof obj;
    }

    function getTicket() {
        return ticketCount++;
    }

    function getStream() {
        return streamCount++;
    }

    // Constructor
    function STU(port) {
        var defPort = 9000;
        var self = this;
        if(!checkExists(port)) {
            port = defPort;
        }

        this.onDCAtimeout = null;

        websocket = new WebSocket("wss://localhost:" + port.toString() + "/ws");

        websocket.onopen = function() {
            console.log("Wacom SigCaptX: Connected to service");
        }
        websocket.onmessage = receive;
        websocket.onclose = function() {
            console.log("Wacom SigCaptX: Disconnected");
            if (typeof self.onDCAtimeout === "function") {
                self.onDCAtimeout();
            }
        }
        websocket.onerror = function(e) {
            console.log("Wacom SigCaptX: WebSocket error", e);
        }
    }

    STU.prototype.Reinitialize = function() {
        WacomGSS.STU = new WacomGSS.STUConstructor();
    }

    STU.prototype.isServiceReady = function() {
        return websocket && 1 == websocket.readyState;
    }

    STU.prototype.isDCAReady = function() {
        var deferred = Q.defer();
        if(!WacomGSS.STU.isServiceReady()) {
            deferred.resolve(false);
        } else {
            setTimeout(function () {
                if(deferred.promise.isPending()) {
                    if(WacomGSS.STU.isServiceReady()) {
                        WacomGSS.STU.close();
                    }
                    deferred.resolve(false);
                }
            }, 1000);
            WacomGSS.STU.getUsbDevices()
            .then(function(message) {
                if(deferred.promise.isPending()) {
                    deferred.resolve(true);
                }
            })
            .fail(function(message) {
                if(deferred.promise.isPending()) {
                    deferred.resolve(true);
                }
            });
        }
        return deferred.promise;
    }

    STU.prototype.close = function() {
        if(websocket) {
            websocket.close();
        }
    }

    function receive(message) {
        if (typeof message.data !== 'undefined' && message.data != "") {
            var arg = JSON.parse(message.data);
            if (checkExists(arg.ticket) && checkExists(table[arg.ticket])) {
                if (checkExists(arg.success) && true == arg.success && checkExists(arg.data)) {
                    table[arg.ticket].resolve(arg.data);
                } else {
                    table[arg.ticket].reject(new Error(checkExists(arg.error) ? arg.error : ""));
                }
                delete table[arg.ticket];
            } else if (checkExists(arg.stream) && checkExists(stream[arg.stream]) && checkExists(arg.data)) {
                stream[arg.stream].stream(arg.data);
            }
        }
    }

    function wsSend(myString) {
        var length = myString.length;
        var pos = 0;
        while (pos < length) {
            var header = (pos + MaxChunkSize < length)? "0" : "1";
            var chunk = myString.substr(pos, MaxChunkSize);
            websocket.send(header + chunk);
            pos += MaxChunkSize;
        }
    }

    STU.prototype.VendorId = { VendorId_Wacom : 0x056a };

    STU.prototype.ProductId = {
        ProductId_500  : 0x00a1,
        ProductId_300  : 0x00a2,
        ProductId_520A : 0x00a3,
        ProductId_430  : 0x00a4,
        ProductId_530  : 0x00a5,
        ProductId_430V : 0x00a6,
        ProductId_540  : 0x00a8,
        ProductId_541  : 0x00a9
    };

    STU.prototype.send = function(object) {
        var deferred = Q.defer();
        try {
            var ticket = getTicket();
            object["ticket"] = ticket;
            var str = JSON.stringify(object);
            wsSend(str);
            table[ticket] = deferred;
        } catch (err) {
            deferred.reject(err);
        }
        return deferred.promise;
    }

    STU.prototype.sendNoReturn = function(object) {
        var str = JSON.stringify(object);
        wsSend(str);
    }

    STU.prototype.setStream = function(functor) {
        var streamId = getStream();
        stream[streamId] = functor;
        return streamId;
    }

    STU.prototype.removeStream = function(streamId) {
        delete stream[streamId];
    }

    // Get USB devices
    STU.prototype.getUsbDevices = function() {
        return this.send({
            "scope": "WacomGSS.STU",
            "function": "getUsbDevices"
        });
    }

    STU.prototype.isSupportedUsbDevice = function(idVendor, idProduct) {
        return this.send({
            "scope": "WacomGSS.STU",
            "function": "isSupportedUsbDevice",
            "idVendor": idVendor,
            "idProduct": idProduct
        });
    }

    // USB Interface
    STU.prototype.UsbInterface = function() {
        this.onReport = null;
        var m_interfaceHandle = null;

        this.Constructor = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.UsbInterface",
                "function": "Constructor"
            }).then(function(message) {
                m_interfaceHandle = message;
                return message;
            });
        }

        this.connect = function(usbDevice, exclusiveLock) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.UsbInterface",
                "function": "connect",
                "interfaceHandle": m_interfaceHandle,
                "usbDevice": usbDevice,
                "exclusiveLock": exclusiveLock
            });
        }

        this.disconnect = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.UsbInterface",
                "function": "disconnect",
                "interfaceHandle": m_interfaceHandle
            });
        }

        this.getInterfaceHandle = function() {
            return m_interfaceHandle;
        }
    }

    // Protocol
    STU.prototype.Protocol = function() {
        this.InkingMode = {
            InkingMode_Off: 0,
            InkingMode_On: 1
        };

        this.EncodingMode = {
            EncodingMode_1bit: 0,
            EncodingMode_16bit: 1,
            EncodingMode_24bit: 2,
            EncodingMode_1bit_Bulk: 3,
            EncodingMode_16bit_Bulk: 4,
            EncodingMode_24bit_Bulk: 5
        };

        this.EncodingFlag = {
            EncodingFlag_1bit: 1,
            EncodingFlag_16bit: 2,
            EncodingFlag_24bit: 4
        };

        this.PenDataOptionMode = {
            PenDataOptionMode_None: 0,
            PenDataOptionMode_TimeCount: 1,
            PenDataOptionMode_SequenceNumber: 2,
            PenDataOptionMode_TimeCountSequence: 3
        };

        this.ReportId = {
            ReportId_PenData: 0x01,
            ReportId_Status: 0x03,
            ReportId_PenDataOptionMode: 0x0B
        };
    }

    // Tablet
    STU.prototype.Tablet = function() {
        var m_tabletHandle = null;

        this.Constructor = function(intf, encH, encH2) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "Constructor",
                "interfaceHandle": intf.getInterfaceHandle(),
                "encH": encH,
                "encH2": encH2
            }).then(function(message) {
                m_tabletHandle = message;
                return message;
            });
        }

        this.getTabletHandle = function() {
            return m_tabletHandle;
        }

        this.getCapability = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "getCapability",
                "tabletHandle": m_tabletHandle
            });
        }

        this.getInformation = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "getInformation",
                "tabletHandle": m_tabletHandle
            });
        }

        this.getInkThreshold = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "getInkThreshold",
                "tabletHandle": m_tabletHandle
            });
        }

        this.getProductId = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "getProductId",
                "tabletHandle": m_tabletHandle
            });
        }

        this.setClearScreen = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "setClearScreen",
                "tabletHandle": m_tabletHandle
            });
        }

        this.setInkingMode = function(inkingMode) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "setInkingMode",
                "tabletHandle": m_tabletHandle,
                "inkingMode": inkingMode
            });
        }

        this.setPenDataOptionMode = function(mode) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "setPenDataOptionMode",
                "tabletHandle": m_tabletHandle,
                "penDataOptionMode": mode
            });
        }

        this.startCapture = function(sessionId) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "startCapture",
                "tabletHandle": m_tabletHandle,
                "sessionId": sessionId
            });
        }

        this.endCapture = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "endCapture",
                "tabletHandle": m_tabletHandle
            });
        }

        this.disconnect = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "disconnect",
                "tabletHandle": m_tabletHandle
            });
        }

        this.isSupported = function(reportId) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "isSupported",
                "tabletHandle": m_tabletHandle,
                "reportId": reportId
            });
        }

        this.supportsWrite = function() {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "supportsWrite",
                "tabletHandle": m_tabletHandle
            });
        }

        this.writeImage = function(encodingMode, b64Data) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.Tablet",
                "function": "writeImage",
                "tabletHandle": m_tabletHandle,
                "encodingMode": encodingMode,
                "imageData": b64Data
            });
        }
    }

    // Protocol Helper
    STU.prototype.ProtocolHelper = {
        simulateEncodingFlag: function(productId, encodingFlag) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.ProtocolHelper",
                "function": "simulateEncodingFlag",
                "idProduct": productId,
                "encodingFlag": encodingFlag
            });
        },

        resizeAndFlatten: function(imageData, offsetX, offsetY, imageWidth, imageHeight, screenWidth, screenHeight, encodingMode, scale, isColor, backgroundColor, preserveAspectRatio) {
            return WacomGSS.STU.send({
                "scope": "WacomGSS.STU.ProtocolHelper",
                "function": "resizeAndFlatten",
                "imageData": imageData,
                "offsetX": offsetX,
                "offsetY": offsetY,
                "imageWidth": imageWidth,
                "imageHeight": imageHeight,
                "screenWidth": screenWidth,
                "screenHeight": screenHeight,
                "encodingMode": encodingMode,
                "scale": scale,
                "isColor": isColor,
                "backgroundColor": backgroundColor,
                "preserveAspectRatio": preserveAspectRatio
            });
        },

        ReportHandler: function() {
            var m_streamId = null;
            var self = this;

            this.onReportPenData = null;
            this.onReportPenDataOption = null;
            this.onReportPenDataTimeCountSequence = null;

            this.startReporting = function(tablet, allData) {
                var functor = {
                    stream: function(data) {
                        if (data.cyclic) {
                            if (self.onReportPenData) self.onReportPenData(data.cyclic);
                        } else if (data.penData) {
                            if (self.onReportPenData) self.onReportPenData(data.penData);
                        } else if (data.penDataOption) {
                            if (self.onReportPenDataOption) self.onReportPenDataOption(data.penDataOption);
                        } else if (data.penDataTimeCountSequence) {
                            if (self.onReportPenDataTimeCountSequence) self.onReportPenDataTimeCountSequence(data.penDataTimeCountSequence);
                        }
                    }
                };
                m_streamId = WacomGSS.STU.setStream(functor);

                return WacomGSS.STU.send({
                    "scope": "WacomGSS.STU.ProtocolHelper.ReportHandler",
                    "function": "startReporting",
                    "tabletHandle": tablet.getTabletHandle(),
                    "allData": allData,
                    "streamId": m_streamId
                });
            }

            this.stopReporting = function() {
                if (m_streamId !== null) {
                    WacomGSS.STU.removeStream(m_streamId);
                    m_streamId = null;
                }
            }
        }
    };

    return STU;
})();

// Initialize the SDK
WacomGSS.STU = new WacomGSS.STUConstructor();
</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const btnCapture = document.getElementById('btnCapture');
    const btnClear = document.getElementById('btnClear');
    const btnSave = document.getElementById('btnSave');
    const signatureImage = document.getElementById('signatureImage');
    const signaturePlaceholder = document.getElementById('signaturePlaceholder');
    const signatureData = document.getElementById('signature_data');
    const signatureForm = document.getElementById('signatureForm');
    const statusMessage = document.getElementById('statusMessage');
    const deviceStatus = document.getElementById('deviceStatus');
    const modalBackground = document.getElementById('modal-background');
    const signatureWindow = document.getElementById('signatureWindow');
    const stuCanvas = document.getElementById('stuCanvas');

    // STU Demo variables
    var tablet = null;
    var m_capability = null;
    var m_inkThreshold = null;
    var m_encodingMode = null;
    var m_usbDevices = null;
    var m_penData = [];
    var m_btns = [];
    var m_imgData = null;
    var reportHandler = null;
    var ctx = null;
    var isDown = false;
    var lastPoint = { x: 0, y: 0 };

    const BTN_TEXT_OK = "OK";
    const BTN_TEXT_CLEAR = "Clear";
    const BTN_TEXT_CANCEL = "Cancel";
    const MAXRETRIES = 20;
    const TIMEOUT_LONG = 1000;
    const TIMEOUT_SHORT = 500;
    var retry = 0;

    // Helper classes
    function Rectangle(x, y, width, height) {
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.Contains = function(pt) {
            return pt.x >= this.x && pt.x <= (this.x + this.width) &&
                   pt.y >= this.y && pt.y <= (this.y + this.height);
        };
    }

    function Button() {
        this.Bounds = null;
        this.Text = "";
        this.Click = null;
    }

    function Point(x, y) {
        return { x: x, y: y };
    }

    // Show status message
    function showMessage(message, type) {
        statusMessage.className = 'alert alert-' + type;
        statusMessage.textContent = message;
        statusMessage.classList.remove('d-none');
        if (type === 'success') {
            setTimeout(function() {
                statusMessage.classList.add('d-none');
            }, 5000);
        }
    }

    // Update device status badge
    function updateDeviceStatus(status, type) {
        deviceStatus.textContent = status;
        deviceStatus.className = 'badge bg-' + type;
    }

    // Check for SigCaptX service
    function checkForSigCaptX() {
        retry++;
        if (WacomGSS.STU.isServiceReady()) {
            retry = 0;
            console.log("SigCaptX Web Service: ready");
            updateDeviceStatus('SigCaptX Connected', 'success');

            // Check for devices
            WacomGSS.STU.getUsbDevices()
            .then(function(devices) {
                m_usbDevices = devices;
                if (devices && devices.length > 0) {
                    console.log("Found STU device:", devices[0]);
                    updateDeviceStatus('STU-' + devices[0].idProduct.toString(16).toUpperCase() + ' Ready', 'success');
                } else {
                    updateDeviceStatus('No STU device found', 'warning');
                    showMessage('No Wacom STU device detected. Please connect your STU-430 and refresh the page.', 'warning');
                }
            })
            .fail(function(error) {
                console.error("Error getting devices:", error);
                updateDeviceStatus('Device error', 'danger');
            });
        } else {
            console.log("SigCaptX Web Service: not connected (attempt " + retry + ")");
            if (retry < MAXRETRIES) {
                setTimeout(checkForSigCaptX, TIMEOUT_LONG);
            } else {
                updateDeviceStatus('SigCaptX not running', 'danger');
                showMessage('Could not connect to Wacom SigCaptX service. Make sure the service is running (check Windows Services for "Wacom STU SigCaptX"). You can use the canvas fallback below.', 'warning');
            }
        }
    }

    setTimeout(checkForSigCaptX, TIMEOUT_SHORT);

    // Create modal window for signature capture
    function createModalWindow(width, height) {
        modalBackground.style.display = 'block';
        signatureWindow.style.display = 'block';
        signatureWindow.style.top = (window.innerHeight / 2 - height / 2) + 'px';
        signatureWindow.style.left = (window.innerWidth / 2 - width / 2) + 'px';
        signatureWindow.style.width = width + 'px';
        signatureWindow.style.height = height + 'px';

        stuCanvas.width = width;
        stuCanvas.height = height;
        ctx = stuCanvas.getContext('2d');

        stuCanvas.addEventListener('click', onCanvasClick);
    }

    function closeModalWindow() {
        modalBackground.style.display = 'none';
        signatureWindow.style.display = 'none';
    }

    // Button functions
    function btnOk_Click() {
        generateImage();
        setTimeout(closeCapture, 0);
    }

    function btnCancel_Click() {
        setTimeout(closeCapture, 0);
    }

    function btnClear_Click() {
        console.log("Clear signature");
        clearScreen();
    }

    // Add buttons to the canvas
    function addButtons() {
        m_btns = [new Button(), new Button(), new Button()];

        if (m_usbDevices[0].idProduct === WacomGSS.STU.ProductId.ProductId_300) {
            // STU-300 - small buttons on right
            var w = Math.round(m_capability.screenWidth / 3);
            var h = Math.round(m_capability.screenHeight / 3);
            var x = m_capability.screenWidth - w;
            m_btns[0].Bounds = new Rectangle(x, 0, w, h);
            m_btns[1].Bounds = new Rectangle(x, h, w, h);
            m_btns[2].Bounds = new Rectangle(x, 2 * h, w, h);
        } else {
            // Other models - buttons across bottom
            var w = Math.round(m_capability.screenWidth / 3);
            var h = Math.round(m_capability.screenHeight / 8);
            var y = m_capability.screenHeight - h;
            m_btns[0].Bounds = new Rectangle(0, y, w, h);
            m_btns[1].Bounds = new Rectangle(w, y, w, h);
            m_btns[2].Bounds = new Rectangle(2 * w, y, w, h);
        }

        m_btns[0].Text = BTN_TEXT_OK;
        m_btns[1].Text = BTN_TEXT_CLEAR;
        m_btns[2].Text = BTN_TEXT_CANCEL;
        m_btns[0].Click = btnOk_Click;
        m_btns[1].Click = btnClear_Click;
        m_btns[2].Click = btnCancel_Click;

        clearCanvas();
        drawButtons();
    }

    function drawButtons() {
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = 'black';
        ctx.font = "20px Arial";

        for (var i = 0; i < m_btns.length; i++) {
            ctx.fillStyle = "lightgrey";
            ctx.fillRect(m_btns[i].Bounds.x, m_btns[i].Bounds.y, m_btns[i].Bounds.width, m_btns[i].Bounds.height);
            ctx.fillStyle = "black";
            ctx.rect(m_btns[i].Bounds.x, m_btns[i].Bounds.y, m_btns[i].Bounds.width, m_btns[i].Bounds.height);
            var xPos = m_btns[i].Bounds.x + (m_btns[i].Bounds.width / 2) - (ctx.measureText(m_btns[i].Text).width / 2);
            var yPos = m_btns[i].Bounds.y + m_btns[i].Bounds.height / 2 + 6;
            ctx.fillText(m_btns[i].Text, xPos, yPos);
        }
        ctx.stroke();
        ctx.closePath();
        ctx.restore();
    }

    function clearCanvas() {
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.fillStyle = "white";
        ctx.fillRect(0, 0, stuCanvas.width, stuCanvas.height);
        ctx.restore();
    }

    function clearScreen() {
        clearCanvas();
        drawButtons();
        m_penData = [];

        // Send cleared image to tablet
        var canvasImage = stuCanvas.toDataURL("image/jpeg");
        WacomGSS.STU.ProtocolHelper.resizeAndFlatten(
            canvasImage, 0, 0, 0, 0,
            m_capability.screenWidth, m_capability.screenHeight,
            m_encodingMode, 1, false, 0, true
        )
        .then(function(imgData) {
            return tablet.writeImage(m_encodingMode, imgData);
        });
    }

    function onCanvasClick(event) {
        var rect = stuCanvas.getBoundingClientRect();
        var posX = event.clientX - rect.left;
        var posY = event.clientY - rect.top;

        for (var i = 0; i < m_btns.length; i++) {
            if (m_btns[i].Bounds.Contains({ x: posX, y: posY })) {
                m_btns[i].Click();
                break;
            }
        }
    }

    function distance(a, b) {
        return Math.pow(a.x - b.x, 2) + Math.pow(a.y - b.y, 2);
    }

    function processButtons(point) {
        var nextPoint = {
            x: Math.round(stuCanvas.width * point.x / m_capability.tabletMaxX),
            y: Math.round(stuCanvas.height * point.y / m_capability.tabletMaxY)
        };
        var isDown2 = isDown ? !(point.pressure <= m_inkThreshold.offPressureMark) : (point.pressure > m_inkThreshold.onPressureMark);

        var btn = -1;
        for (var i = 0; i < m_btns.length; i++) {
            if (m_btns[i].Bounds.Contains(nextPoint)) {
                btn = i;
                break;
            }
        }

        if (isDown && !isDown2 && btn !== -1) {
            m_btns[btn].Click();
        }
        return btn === -1;
    }

    function processPoint(point) {
        var nextPoint = {
            x: Math.round(stuCanvas.width * point.x / m_capability.tabletMaxX),
            y: Math.round(stuCanvas.height * point.y / m_capability.tabletMaxY)
        };
        var isDown2 = isDown ? !(point.pressure <= m_inkThreshold.offPressureMark) : (point.pressure > m_inkThreshold.onPressureMark);

        if (!isDown && isDown2) {
            lastPoint = nextPoint;
        }

        if ((isDown2 && 10 < distance(lastPoint, nextPoint)) || (isDown && !isDown2)) {
            ctx.beginPath();
            ctx.moveTo(lastPoint.x, lastPoint.y);
            ctx.lineTo(nextPoint.x, nextPoint.y);
            ctx.stroke();
            ctx.closePath();
            lastPoint = nextPoint;
        }

        isDown = isDown2;
    }

    function generateImage() {
        // Create a clean canvas with just the signature (no buttons)
        var sigCanvas = document.createElement("canvas");
        sigCanvas.width = stuCanvas.width;
        sigCanvas.height = stuCanvas.height - (m_btns[0] ? m_btns[0].Bounds.height : 0);
        var sigCtx = sigCanvas.getContext("2d");

        sigCtx.fillStyle = "white";
        sigCtx.fillRect(0, 0, sigCanvas.width, sigCanvas.height);
        sigCtx.strokeStyle = "black";
        sigCtx.lineWidth = 2;

        lastPoint = { x: 0, y: 0 };
        isDown = false;

        for (var i = 0; i < m_penData.length; i++) {
            var pt = m_penData[i];
            var nextPoint = {
                x: Math.round(sigCanvas.width * pt.x / m_capability.tabletMaxX),
                y: Math.round(sigCanvas.height * pt.y / m_capability.tabletMaxY)
            };
            var isDown2 = isDown ? !(pt.pressure <= m_inkThreshold.offPressureMark) : (pt.pressure > m_inkThreshold.onPressureMark);

            if (!isDown && isDown2) {
                lastPoint = nextPoint;
            }

            if ((isDown2 && 10 < distance(lastPoint, nextPoint)) || (isDown && !isDown2)) {
                sigCtx.beginPath();
                sigCtx.moveTo(lastPoint.x, lastPoint.y);
                sigCtx.lineTo(nextPoint.x, nextPoint.y);
                sigCtx.stroke();
                sigCtx.closePath();
                lastPoint = nextPoint;
            }
            isDown = isDown2;
        }

        var dataUrl = sigCanvas.toDataURL("image/png");
        displaySignature(dataUrl);
        showMessage('Signature captured successfully!', 'success');
    }

    function closeCapture() {
        WacomGSS.STU.onDCAtimeout = null;
        disconnect();
        closeModalWindow();
        btnCapture.disabled = false;
    }

    function disconnect() {
        if (tablet) {
            var p = new WacomGSS.STU.Protocol();
            tablet.setInkingMode(p.InkingMode.InkingMode_Off)
            .then(function() {
                return tablet.endCapture();
            })
            .then(function() {
                return tablet.setClearScreen();
            })
            .then(function() {
                return tablet.disconnect();
            })
            .then(function() {
                tablet = null;
            })
            .fail(function(error) {
                console.log("Disconnect error:", error);
                tablet = null;
            });
        }
        if (reportHandler) {
            reportHandler.stopReporting();
            reportHandler = null;
        }
    }

    // Start capture demo
    function startCapture() {
        var p = new WacomGSS.STU.Protocol();
        var intf;

        WacomGSS.STU.isDCAReady()
        .then(function(ready) {
            if (!ready) {
                throw new Error("SigCaptX service not ready");
            }
            return WacomGSS.STU.getUsbDevices();
        })
        .then(function(devices) {
            if (!devices || devices.length === 0) {
                throw new Error("No STU devices found");
            }
            m_usbDevices = devices;
            console.log("Found device:", devices[0]);
            return WacomGSS.STU.isSupportedUsbDevice(devices[0].idVendor, devices[0].idProduct);
        })
        .then(function() {
            intf = new WacomGSS.STU.UsbInterface();
            return intf.Constructor();
        })
        .then(function() {
            return intf.connect(m_usbDevices[0], true);
        })
        .then(function() {
            tablet = new WacomGSS.STU.Tablet();
            return tablet.Constructor(intf, null, null);
        })
        .then(function() {
            return tablet.getInkThreshold();
        })
        .then(function(inkThreshold) {
            m_inkThreshold = inkThreshold;
            return tablet.getCapability();
        })
        .then(function(capability) {
            m_capability = capability;
            console.log("Tablet capability:", capability);
            createModalWindow(capability.screenWidth, capability.screenHeight);
            return tablet.getProductId();
        })
        .then(function(productId) {
            return WacomGSS.STU.ProtocolHelper.simulateEncodingFlag(productId, m_capability.encodingFlag);
        })
        .then(function(encodingFlag) {
            var p = new WacomGSS.STU.Protocol();
            if ((encodingFlag & p.EncodingFlag.EncodingFlag_24bit) !== 0) {
                return tablet.supportsWrite().then(function(supports) {
                    m_encodingMode = supports ? p.EncodingMode.EncodingMode_24bit_Bulk : p.EncodingMode.EncodingMode_24bit;
                });
            } else if ((encodingFlag & p.EncodingFlag.EncodingFlag_16bit) !== 0) {
                return tablet.supportsWrite().then(function(supports) {
                    m_encodingMode = supports ? p.EncodingMode.EncodingMode_16bit_Bulk : p.EncodingMode.EncodingMode_16bit;
                });
            } else {
                m_encodingMode = p.EncodingMode.EncodingMode_1bit;
            }
        })
        .then(function() {
            return tablet.setClearScreen();
        })
        .then(function() {
            return tablet.isSupported(p.ReportId.ReportId_PenDataOptionMode);
        })
        .then(function(supported) {
            if (supported) {
                return tablet.getProductId().then(function(productId) {
                    var mode = p.PenDataOptionMode.PenDataOptionMode_None;
                    if (productId === WacomGSS.STU.ProductId.ProductId_430 ||
                        productId === WacomGSS.STU.ProductId.ProductId_530 ||
                        productId === WacomGSS.STU.ProductId.ProductId_540) {
                        mode = p.PenDataOptionMode.PenDataOptionMode_TimeCountSequence;
                    } else if (productId === WacomGSS.STU.ProductId.ProductId_520A) {
                        mode = p.PenDataOptionMode.PenDataOptionMode_TimeCount;
                    }
                    return tablet.setPenDataOptionMode(mode);
                });
            }
        })
        .then(function() {
            addButtons();
            var canvasImage = stuCanvas.toDataURL("image/jpeg");
            return WacomGSS.STU.ProtocolHelper.resizeAndFlatten(
                canvasImage, 0, 0, 0, 0,
                m_capability.screenWidth, m_capability.screenHeight,
                m_encodingMode, 1, false, 0, true
            );
        })
        .then(function(imgData) {
            m_imgData = imgData;
            return tablet.writeImage(m_encodingMode, imgData);
        })
        .then(function() {
            return tablet.setInkingMode(p.InkingMode.InkingMode_On);
        })
        .then(function() {
            reportHandler = new WacomGSS.STU.ProtocolHelper.ReportHandler();
            lastPoint = { x: 0, y: 0 };
            isDown = false;
            ctx.lineWidth = 2;
            ctx.strokeStyle = 'black';

            var penDataHandler = function(report) {
                processButtons(report);
                processPoint(report);
                m_penData.push(report);
            };

            m_penData = [];
            reportHandler.onReportPenData = penDataHandler;
            reportHandler.onReportPenDataOption = penDataHandler;
            reportHandler.onReportPenDataTimeCountSequence = penDataHandler;

            return reportHandler.startReporting(tablet, true);
        })
        .then(function() {
            console.log("Signature capture started");
            showMessage('Sign on the Wacom device. Press OK when done, Clear to retry, or Cancel to abort.', 'info');
        })
        .fail(function(error) {
            console.error("Capture error:", error);
            showMessage('Error starting capture: ' + error.message, 'danger');
            btnCapture.disabled = false;
            closeModalWindow();
        });
    }

    // Capture button click
    btnCapture.addEventListener('click', function() {
        if (!WacomGSS.STU.isServiceReady()) {
            showMessage('SigCaptX service not connected. Please use the canvas fallback below.', 'warning');
            return;
        }

        btnCapture.disabled = true;
        showMessage('Initializing capture...', 'info');
        startCapture();
    });

    // Display captured signature
    function displaySignature(dataUrl) {
        signatureData.value = dataUrl;
        signatureImage.src = dataUrl;
        signatureImage.style.display = 'block';
        signaturePlaceholder.style.display = 'none';
        btnClear.disabled = false;
        btnSave.disabled = false;
    }

    // Clear signature
    btnClear.addEventListener('click', function() {
        signatureData.value = '';
        signatureImage.src = '';
        signatureImage.style.display = 'none';
        signaturePlaceholder.style.display = 'block';
        btnClear.disabled = true;
        btnSave.disabled = true;
    });

    // Save signature
    signatureForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!signatureData.value) {
            showMessage('Please capture a signature first', 'warning');
            return;
        }

        if (!document.getElementById('client_name').value) {
            showMessage('Please enter client name', 'warning');
            return;
        }

        const formData = {
            client_name: document.getElementById('client_name').value,
            client_reference: document.getElementById('client_reference').value,
            id_number: document.getElementById('id_number').value,
            purpose: document.getElementById('purpose').value,
            document_reference: document.getElementById('document_reference').value,
            signature_data: signatureData.value,
            _token: '{{ csrf_token() }}'
        };

        fetch('{{ route("signatures.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Signature saved successfully!', 'success');
                setTimeout(function() {
                    window.location.href = '{{ route("signatures.index") }}';
                }, 1500);
            } else {
                showMessage('Error saving signature: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            showMessage('Error saving signature: ' + error.message, 'danger');
        });
    });

    // ========== Fallback Canvas Drawing ==========
    const fallbackCanvas = document.getElementById('fallbackCanvas');
    const fallbackCtx = fallbackCanvas.getContext('2d');
    let fallbackDrawing = false;
    let fallbackLastX = 0;
    let fallbackLastY = 0;

    fallbackCtx.fillStyle = '#fff';
    fallbackCtx.fillRect(0, 0, fallbackCanvas.width, fallbackCanvas.height);
    fallbackCtx.strokeStyle = '#000';
    fallbackCtx.lineWidth = 2;
    fallbackCtx.lineCap = 'round';

    function getPos(e) {
        const rect = fallbackCanvas.getBoundingClientRect();
        if (e.touches) {
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top
            };
        }
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    fallbackCanvas.addEventListener('mousedown', function(e) {
        fallbackDrawing = true;
        const pos = getPos(e);
        fallbackLastX = pos.x;
        fallbackLastY = pos.y;
    });

    fallbackCanvas.addEventListener('mousemove', function(e) {
        if (!fallbackDrawing) return;
        const pos = getPos(e);
        fallbackCtx.beginPath();
        fallbackCtx.moveTo(fallbackLastX, fallbackLastY);
        fallbackCtx.lineTo(pos.x, pos.y);
        fallbackCtx.stroke();
        fallbackLastX = pos.x;
        fallbackLastY = pos.y;
    });

    fallbackCanvas.addEventListener('mouseup', () => fallbackDrawing = false);
    fallbackCanvas.addEventListener('mouseout', () => fallbackDrawing = false);

    // Touch support
    fallbackCanvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        fallbackDrawing = true;
        const pos = getPos(e);
        fallbackLastX = pos.x;
        fallbackLastY = pos.y;
    });

    fallbackCanvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        if (!fallbackDrawing) return;
        const pos = getPos(e);
        fallbackCtx.beginPath();
        fallbackCtx.moveTo(fallbackLastX, fallbackLastY);
        fallbackCtx.lineTo(pos.x, pos.y);
        fallbackCtx.stroke();
        fallbackLastX = pos.x;
        fallbackLastY = pos.y;
    });

    fallbackCanvas.addEventListener('touchend', () => fallbackDrawing = false);

    document.getElementById('btnClearFallback').addEventListener('click', function() {
        fallbackCtx.fillStyle = '#fff';
        fallbackCtx.fillRect(0, 0, fallbackCanvas.width, fallbackCanvas.height);
    });

    document.getElementById('btnUseFallback').addEventListener('click', function() {
        const dataUrl = fallbackCanvas.toDataURL('image/png');
        displaySignature(dataUrl);
        showMessage('Signature loaded. Fill in details and click Save.', 'info');
    });
});
</script>
@endpush
@endsection
