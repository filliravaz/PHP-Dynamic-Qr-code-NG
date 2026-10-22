/**
 * Real-time Sticky Live QR Code Preview for PHP-Dynamic-Qr-code-NG
 * Zero external network calls; pure client-side canvas generation
 * Supports Linear/Radial Gradients, Eye Corner Marker Styling, and Custom Punchout Shapes
 */
(function (window, document, $) {
    'use strict';

    // -------------------------------------------------------------
    // Compact Embedded Client-side QRCode Generator (Kazuhiko Arase)
    // -------------------------------------------------------------
    function QR8bitByte(data) {
        this.mode = 4;
        this.data = data;
    }
    QR8bitByte.prototype = {
        getLength: function () {
            return this.data.length;
        },
        write: function (buffer) {
            for (var i = 0; i < this.data.length; i++) {
                buffer.put(this.data.charCodeAt(i), 8);
            }
        }
    };

    var QRMode = { MODE_8BIT_BYTE: 4 };
    var QRErrorCorrectLevel = { L: 1, M: 0, Q: 3, H: 2 };
    var QRMaskPattern = {
        PATTERN000: 0, PATTERN001: 1, PATTERN010: 2, PATTERN011: 3,
        PATTERN100: 4, PATTERN101: 5, PATTERN110: 6, PATTERN111: 7
    };

    var QRUtil = {
        PATTERN_POSITION_TABLE: [
            [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34],
            [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50], [6, 30, 54],
            [6, 32, 58], [6, 34, 62], [6, 26, 46, 66], [6, 26, 48, 70],
            [6, 26, 50, 74], [6, 30, 54, 78], [6, 30, 56, 82], [6, 30, 58, 86],
            [6, 34, 62, 90], [6, 28, 50, 72, 94], [6, 26, 50, 74, 98],
            [6, 30, 54, 78, 102], [6, 28, 54, 80, 106], [6, 32, 58, 84, 110],
            [6, 30, 58, 86, 114], [6, 34, 62, 90, 118], [6, 26, 50, 74, 98, 122],
            [6, 30, 54, 78, 102, 126], [6, 26, 52, 78, 104, 130], [6, 30, 56, 82, 108, 134],
            [6, 34, 60, 86, 112, 138], [6, 30, 58, 86, 114, 142], [6, 34, 62, 90, 118, 146],
            [6, 30, 54, 78, 102, 126, 150], [6, 24, 50, 76, 102, 128, 154],
            [6, 28, 54, 80, 106, 132, 158], [6, 32, 58, 84, 110, 136, 162],
            [6, 26, 54, 82, 110, 138, 166], [6, 30, 58, 86, 114, 142, 170]
        ],
        G15: (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0),
        G18: (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0),
        G15_MASK: (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1),
        getBCHTypeInfo: function (data) {
            var d = data << 10;
            while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15) >= 0) {
                d ^= (QRUtil.G15 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15)));
            }
            return ((data << 10) | d) ^ QRUtil.G15_MASK;
        },
        getBCHTypeNumber: function (data) {
            var d = data << 12;
            while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18) >= 0) {
                d ^= (QRUtil.G18 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18)));
            }
            return (data << 12) | d;
        },
        getBCHDigit: function (data) {
            var digit = 0;
            while (data !== 0) {
                digit++;
                data >>>= 1;
            }
            return digit;
        },
        getPatternPosition: function (typeNumber) {
            return QRUtil.PATTERN_POSITION_TABLE[typeNumber - 1];
        },
        getMask: function (maskPattern, i, j) {
            switch (maskPattern) {
                case QRMaskPattern.PATTERN000: return (i + j) % 2 === 0;
                case QRMaskPattern.PATTERN001: return i % 2 === 0;
                case QRMaskPattern.PATTERN010: return j % 3 === 0;
                case QRMaskPattern.PATTERN011: return (i + j) % 3 === 0;
                case QRMaskPattern.PATTERN100: return (Math.floor(i / 2) + Math.floor(j / 3)) % 2 === 0;
                case QRMaskPattern.PATTERN101: return (i * j) % 2 + (i * j) % 3 === 0;
                case QRMaskPattern.PATTERN110: return ((i * j) % 2 + (i * j) % 3) % 2 === 0;
                case QRMaskPattern.PATTERN111: return ((i * j) % 3 + (i + j) % 2) % 2 === 0;
                default: throw new Error("bad maskPattern:" + maskPattern);
            }
        },
        getErrorCorrectPolynomial: function (errorCorrectLength) {
            var a = new QRPolynomial([1], 0);
            for (var i = 0; i < errorCorrectLength; i++) {
                a = a.multiply(new QRPolynomial([1, QRMath.gexp(i)], 0));
            }
            return a;
        },
        getLengthInBits: function (mode, type) {
            if (1 <= type && type < 10) {
                return 8;
            } else if (type < 27) {
                return 16;
            } else {
                return 16;
            }
        },
        getLostPoint: function (qrCode) {
            var moduleCount = qrCode.getModuleCount();
            var lostPoint = 0;
            for (var row = 0; row < moduleCount; row++) {
                for (var col = 0; col < moduleCount; col++) {
                    var sameCount = 0;
                    var dark = qrCode.isDark(row, col);
                    for (var r = -1; r <= 1; r++) {
                        if (row + r < 0 || moduleCount <= row + r) continue;
                        for (var c = -1; c <= 1; c++) {
                            if (col + c < 0 || moduleCount <= col + c) continue;
                            if (r === 0 && c === 0) continue;
                            if (dark === qrCode.isDark(row + r, col + c)) sameCount++;
                        }
                    }
                    if (sameCount > 5) lostPoint += (3 + sameCount - 5);
                }
            }
            return lostPoint;
        }
    };

    var QRMath = {
        glog: function (n) {
            if (n < 1) throw new Error("glog(" + n + ")");
            return QRMath.LOG_TABLE[n];
        },
        gexp: function (n) {
            while (n < 0) n += 255;
            while (n >= 255) n -= 255;
            return QRMath.EXP_TABLE[n];
        },
        EXP_TABLE: new Array(256),
        LOG_TABLE: new Array(256)
    };
    for (var i = 0; i < 8; i++) QRMath.EXP_TABLE[i] = 1 << i;
    for (var i = 8; i < 256; i++) QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4] ^ QRMath.EXP_TABLE[i - 5] ^ QRMath.EXP_TABLE[i - 6] ^ QRMath.EXP_TABLE[i - 8];
    for (var i = 0; i < 255; i++) QRMath.LOG_TABLE[QRMath.EXP_TABLE[i]] = i;

    function QRPolynomial(num, shift) {
        if (num.length === undefined) throw new Error(num.length + "/" + shift);
        var offset = 0;
        while (offset < num.length && num[offset] === 0) offset++;
        this.num = new Array(num.length - offset + shift);
        for (var i = 0; i < num.length - offset; i++) this.num[i] = num[i + offset];
    }
    QRPolynomial.prototype = {
        get: function (index) { return this.num[index]; },
        getLength: function () { return this.num.length; },
        multiply: function (e) {
            var num = new Array(this.getLength() + e.getLength() - 1);
            for (var i = 0; i < this.getLength(); i++) {
                for (var j = 0; j < e.getLength(); j++) {
                    num[i + j] ^= QRMath.gexp(QRMath.glog(this.get(i)) + QRMath.glog(e.get(j)));
                }
            }
            return new QRPolynomial(num, 0);
        },
        mod: function (e) {
            if (this.getLength() - e.getLength() < 0) return this;
            var ratio = QRMath.glog(this.get(0)) - QRMath.glog(e.get(0));
            var num = new Array(this.getLength());
            for (var i = 0; i < this.getLength(); i++) num[i] = this.get(i);
            for (var i = 0; i < e.getLength(); i++) num[i] ^= QRMath.gexp(QRMath.glog(e.get(i)) + ratio);
            return new QRPolynomial(num, 0).mod(e);
        }
    };

    function QRRSBlock(totalCount, dataCount) {
        this.totalCount = totalCount;
        this.dataCount = dataCount;
    }
    QRRSBlock.RS_BLOCK_TABLE = [
        [1, 26, 19], [1, 26, 16], [1, 26, 13], [1, 26, 9],
        [1, 44, 34], [1, 44, 28], [1, 44, 22], [1, 44, 16],
        [1, 70, 55], [1, 70, 44], [2, 35, 17], [2, 35, 13],
        [1, 100, 80], [2, 50, 32], [2, 50, 24], [4, 25, 9],
        [1, 134, 108], [2, 67, 43], [2, 33, 15, 2, 34, 16], [2, 33, 11, 2, 34, 12],
        [2, 86, 68], [4, 43, 27], [4, 43, 19], [4, 43, 15],
        [2, 98, 78], [4, 49, 31], [2, 32, 14, 4, 33, 15], [4, 39, 13, 1, 40, 14],
        [2, 121, 97], [2, 60, 38, 2, 61, 39], [4, 40, 18, 2, 41, 19], [4, 40, 14, 2, 41, 15],
        [2, 146, 116], [3, 58, 36, 2, 59, 37], [4, 36, 16, 4, 37, 17], [4, 36, 12, 4, 37, 13],
        [2, 86, 68, 2, 87, 69], [4, 69, 43, 1, 70, 44], [6, 43, 19, 2, 44, 20], [6, 43, 15, 2, 44, 16],
        [4, 101, 81], [1, 80, 50, 4, 81, 51], [4, 50, 22, 4, 51, 23], [3, 36, 12, 8, 37, 13],
        [2, 116, 92, 2, 117, 93], [6, 58, 36, 2, 59, 37], [4, 46, 20, 6, 47, 21], [7, 42, 14, 4, 43, 15],
        [4, 133, 107], [8, 59, 37, 1, 60, 38], [8, 44, 20, 4, 45, 21], [12, 33, 11, 4, 34, 12],
        [3, 145, 115, 1, 146, 116], [4, 64, 40, 5, 65, 41], [11, 36, 16, 5, 37, 17], [11, 36, 12, 5, 37, 13],
        [5, 109, 87, 1, 110, 88], [5, 65, 41, 5, 66, 42], [5, 54, 24, 7, 55, 25], [11, 36, 12, 7, 37, 13],
        [5, 122, 98, 1, 123, 99], [7, 73, 45, 3, 74, 46], [15, 43, 19, 2, 44, 20], [3, 45, 15, 13, 46, 16],
        [1, 135, 107, 5, 136, 108], [10, 74, 46, 1, 75, 47], [1, 50, 22, 15, 51, 23], [2, 42, 14, 17, 43, 15],
        [5, 150, 120, 1, 151, 121], [9, 69, 43, 4, 70, 44], [17, 50, 22, 1, 51, 23], [2, 42, 14, 19, 43, 15],
        [3, 141, 113, 4, 142, 114], [3, 70, 44, 11, 71, 45], [17, 47, 21, 4, 48, 22], [9, 39, 13, 16, 40, 14],
        [3, 135, 107, 5, 136, 108], [3, 67, 41, 13, 68, 42], [15, 54, 24, 5, 55, 25], [15, 43, 15, 10, 44, 16]
    ];
    QRRSBlock.getRSBlocks = function (typeNumber, errorCorrectLevel) {
        var rsBlock = QRRSBlock.getRsBlockTable(typeNumber, errorCorrectLevel);
        if (rsBlock === undefined) throw new Error("bad rs block @ typeNumber:" + typeNumber);
        var length = rsBlock.length / 3;
        var list = [];
        for (var i = 0; i < length; i++) {
            var count = rsBlock[i * 3 + 0];
            var totalCount = rsBlock[i * 3 + 1];
            var dataCount = rsBlock[i * 3 + 2];
            for (var j = 0; j < count; j++) list.push(new QRRSBlock(totalCount, dataCount));
        }
        return list;
    };
    QRRSBlock.getRsBlockTable = function (typeNumber, errorCorrectLevel) {
        switch (errorCorrectLevel) {
            case QRErrorCorrectLevel.L: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 0];
            case QRErrorCorrectLevel.M: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 1];
            case QRErrorCorrectLevel.Q: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 2];
            case QRErrorCorrectLevel.H: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 3];
            default: return undefined;
        }
    };

    function QRBitBuffer() {
        this.buffer = [];
        this.length = 0;
    }
    QRBitBuffer.prototype = {
        get: function (index) {
            var bufIndex = Math.floor(index / 8);
            return ((this.buffer[bufIndex] >>> (7 - index % 8)) & 1) === 1;
        },
        put: function (num, length) {
            for (var i = 0; i < length; i++) {
                this.putBit(((num >>> (length - i - 1)) & 1) === 1);
            }
        },
        putBit: function (bit) {
            var bufIndex = Math.floor(this.length / 8);
            if (this.buffer.length <= bufIndex) this.buffer.push(0);
            if (bit) this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
            this.length++;
        }
    };

    function QRCodeModel(typeNumber, errorCorrectLevel) {
        this.typeNumber = typeNumber;
        this.errorCorrectLevel = errorCorrectLevel;
        this.modules = null;
        this.moduleCount = 0;
        this.dataCache = null;
        this.dataList = [];
    }
    QRCodeModel.prototype = {
        addData: function (data) {
            var newData = new QR8bitByte(data);
            this.dataList.push(newData);
            this.dataCache = null;
        },
        isDark: function (row, col) {
            if (row < 0 || this.moduleCount <= row || col < 0 || this.moduleCount <= col) {
                throw new Error(row + "," + col);
            }
            return this.modules[row][col];
        },
        getModuleCount: function () {
            return this.moduleCount;
        },
        make: function () {
            if (this.typeNumber < 1) {
                var typeNumber = 1;
                for (typeNumber = 1; typeNumber < 40; typeNumber++) {
                    var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, this.errorCorrectLevel);
                    var buffer = new QRBitBuffer();
                    var totalDataCount = 0;
                    for (var i = 0; i < rsBlocks.length; i++) totalDataCount += rsBlocks[i].dataCount;
                    for (var i = 0; i < this.dataList.length; i++) {
                        var data = this.dataList[i];
                        buffer.put(data.mode, 4);
                        buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
                        data.write(buffer);
                    }
                    if (buffer.length <= totalDataCount * 8) break;
                }
                this.typeNumber = typeNumber;
            }
            this.makeImpl(false, this.getBestMaskPattern());
        },
        makeImpl: function (test, maskPattern) {
            this.moduleCount = this.typeNumber * 4 + 17;
            this.modules = new Array(this.moduleCount);
            for (var row = 0; row < this.moduleCount; row++) {
                this.modules[row] = new Array(this.moduleCount);
                for (var col = 0; col < this.moduleCount; col++) this.modules[row][col] = null;
            }
            this.setupPositionProbePattern(0, 0);
            this.setupPositionProbePattern(this.moduleCount - 7, 0);
            this.setupPositionProbePattern(0, this.moduleCount - 7);
            this.setupPositionAdjustPattern();
            this.setupTimingPattern();
            this.setupTypeInfo(test, maskPattern);
            if (this.typeNumber >= 7) this.setupTypeNumber(test);
            if (this.dataCache === null) this.dataCache = QRCodeModel.createData(this.typeNumber, this.errorCorrectLevel, this.dataList);
            this.mapData(this.dataCache, maskPattern);
        },
        setupPositionProbePattern: function (row, col) {
            for (var r = -1; r <= 7; r++) {
                if (row + r <= -1 || this.moduleCount <= row + r) continue;
                for (var c = -1; c <= 7; c++) {
                    if (col + c <= -1 || this.moduleCount <= col + c) continue;
                    if ((0 <= r && r <= 6 && (c === 0 || c === 6)) || (0 <= c && c <= 6 && (r === 0 || r === 6)) || (2 <= r && r <= 4 && 2 <= c && c <= 4)) {
                        this.modules[row + r][col + c] = true;
                    } else {
                        this.modules[row + r][col + c] = false;
                    }
                }
            }
        },
        getBestMaskPattern: function () {
            var minLostPoint = 0;
            var pattern = 0;
            for (var i = 0; i < 8; i++) {
                this.makeImpl(true, i);
                var lostPoint = QRUtil.getLostPoint(this);
                if (i === 0 || minLostPoint > lostPoint) {
                    minLostPoint = lostPoint;
                    pattern = i;
                }
            }
            return pattern;
        },
        setupTimingPattern: function () {
            for (var r = 8; r < this.moduleCount - 8; r++) {
                if (this.modules[r][6] !== null) continue;
                this.modules[r][6] = (r % 2 === 0);
            }
            for (var c = 8; c < this.moduleCount - 8; c++) {
                if (this.modules[6][c] !== null) continue;
                this.modules[6][c] = (c % 2 === 0);
            }
        },
        setupPositionAdjustPattern: function () {
            var pos = QRUtil.getPatternPosition(this.typeNumber);
            if (!pos) return;
            for (var i = 0; i < pos.length; i++) {
                for (var j = 0; j < pos.length; j++) {
                    var row = pos[i];
                    var col = pos[j];
                    if (this.modules[row][col] !== null) continue;
                    for (var r = -2; r <= 2; r++) {
                        for (var c = -2; c <= 2; c++) {
                            if (Math.abs(r) === 2 || Math.abs(c) === 2 || (r === 0 && c === 0)) {
                                this.modules[row + r][col + c] = true;
                            } else {
                                this.modules[row + r][col + c] = false;
                            }
                        }
                    }
                }
            }
        },
        setupTypeNumber: function (test) {
            var bits = QRUtil.getBCHTypeNumber(this.typeNumber);
            for (var i = 0; i < 18; i++) {
                var mod = (!test && ((bits >> i) & 1) === 1);
                this.modules[Math.floor(i / 3)][i % 3 + this.moduleCount - 8 - 3] = mod;
                this.modules[i % 3 + this.moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
            }
        },
        setupTypeInfo: function (test, maskPattern) {
            var data = (this.errorCorrectLevel << 3) | maskPattern;
            var bits = QRUtil.getBCHTypeInfo(data);
            for (var i = 0; i < 15; i++) {
                var mod = (!test && ((bits >> i) & 1) === 1);
                if (i < 6) this.modules[i][8] = mod;
                else if (i < 8) this.modules[i + 1][8] = mod;
                else this.modules[this.moduleCount - 15 + i][8] = mod;

                if (i < 8) this.modules[8][this.moduleCount - i - 1] = mod;
                else if (i < 9) this.modules[8][15 - i - 1 + 1] = mod;
                else this.modules[8][15 - i - 1] = mod;
            }
            this.modules[this.moduleCount - 8][8] = !test;
        },
        mapData: function (data, maskPattern) {
            var inc = -1;
            var row = this.moduleCount - 1;
            var bitIndex = 7;
            var byteIndex = 0;
            for (var col = this.moduleCount - 1; col > 0; col -= 2) {
                if (col === 6) col--;
                while (true) {
                    for (var c = 0; c < 2; c++) {
                        if (this.modules[row][col - c] === null) {
                            var dark = false;
                            if (byteIndex < data.length) {
                                dark = (((data[byteIndex] >>> bitIndex) & 1) === 1);
                            }
                            var mask = QRUtil.getMask(maskPattern, row, col - c);
                            if (mask) dark = !dark;
                            this.modules[row][col - c] = dark;
                            bitIndex--;
                            if (bitIndex === -1) {
                                byteIndex++;
                                bitIndex = 7;
                            }
                        }
                    }
                    row += inc;
                    if (row < 0 || this.moduleCount <= row) {
                        row -= inc;
                        inc = -inc;
                        break;
                    }
                }
            }
        }
    };

    QRCodeModel.createData = function (typeNumber, errorCorrectLevel, dataList) {
        var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectLevel);
        var buffer = new QRBitBuffer();
        for (var i = 0; i < dataList.length; i++) {
            var data = dataList[i];
            buffer.put(data.mode, 4);
            buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
            data.write(buffer);
        }
        var totalDataCount = 0;
        for (var i = 0; i < rsBlocks.length; i++) totalDataCount += rsBlocks[i].dataCount;
        if (buffer.length + 4 <= totalDataCount * 8) buffer.put(0, 4);
        while (buffer.length % 8 !== 0) buffer.putBit(false);
        while (true) {
            if (buffer.length >= totalDataCount * 8) break;
            buffer.put(0xEC, 8);
            if (buffer.length >= totalDataCount * 8) break;
            buffer.put(0x11, 8);
        }
        return QRCodeModel.createBytes(buffer, rsBlocks);
    };

    QRCodeModel.createBytes = function (buffer, rsBlocks) {
        var offset = 0;
        var maxDcCount = 0;
        var maxEcCount = 0;
        var dcdata = new Array(rsBlocks.length);
        var ecdata = new Array(rsBlocks.length);
        for (var r = 0; r < rsBlocks.length; r++) {
            var dcCount = rsBlocks[r].dataCount;
            var ecCount = rsBlocks[r].totalCount - dcCount;
            maxDcCount = Math.max(maxDcCount, dcCount);
            maxEcCount = Math.max(maxEcCount, ecCount);
            dcdata[r] = new Array(dcCount);
            for (var i = 0; i < dcdata[r].length; i++) dcdata[r][i] = 0xff & buffer.buffer[i + offset];
            offset += dcCount;
            var rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
            var rawPoly = new QRPolynomial(dcdata[r], rsPoly.getLength() - 1);
            var modPoly = rawPoly.mod(rsPoly);
            ecdata[r] = new Array(rsPoly.getLength() - 1);
            for (var i = 0; i < ecdata[r].length; i++) {
                var modIndex = i + modPoly.getLength() - ecdata[r].length;
                ecdata[r][i] = (modIndex >= 0) ? modPoly.get(modIndex) : 0;
            }
        }
        var totalCodeCount = 0;
        for (var i = 0; i < rsBlocks.length; i++) totalCodeCount += rsBlocks[i].totalCount;
        var data = new Array(totalCodeCount);
        var index = 0;
        for (var i = 0; i < maxDcCount; i++) {
            for (var r = 0; r < rsBlocks.length; r++) {
                if (i < dcdata[r].length) data[index++] = dcdata[r][i];
            }
        }
        for (var i = 0; i < maxEcCount; i++) {
            for (var r = 0; r < rsBlocks.length; r++) {
                if (i < ecdata[r].length) data[index++] = ecdata[r][i];
            }
        }
        return data;
    };

    // -------------------------------------------------------------
    // Live Preview Engine Controller
    // -------------------------------------------------------------
    var customLogoDataUrl = null;
    var presetLogoName = '';
    var renderTimer = null;

    function toValidColor(color, fallback) {
        if (!color || typeof color !== 'string') return fallback;
        color = color.trim();
        if (/^[0-9a-fA-F]{3,8}$/.test(color)) {
            return '#' + color;
        }
        return color || fallback;
    }

    function hexToRgb(hex, fallback) {
        if (!hex || typeof hex !== 'string') return fallback || { r: 0, g: 0, b: 0 };
        hex = hex.replace(/^#/, '').trim();
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        if (/^[0-9a-fA-F]{6}$/.test(hex)) {
            return {
                r: parseInt(hex.substring(0, 2), 16),
                g: parseInt(hex.substring(2, 4), 16),
                b: parseInt(hex.substring(4, 6), 16)
            };
        }
        return fallback || { r: 0, g: 0, b: 0 };
    }

    function getActiveForm() {
        if ($('#dynamic_form').length) {
            return $('#dynamic_form');
        }
        var activeTabPane = $('.tab-content .tab-pane.active');
        if (activeTabPane.length && activeTabPane.find('form').length) {
            return activeTabPane.find('form');
        }
        return $('form').first();
    }

    function extractFormData() {
        var $form = getActiveForm();
        var rawFg = $form.find('.input-foreground').val() || $form.find('input[name="foreground"]').val() || '#000000';
        var rawBg = $form.find('.input-background').val() || $form.find('input[name="background"]').val() || '#ffffff';
        var fg = toValidColor(rawFg, '#000000');
        var bg = toValidColor(rawBg, '#ffffff');

        var level = $form.find('.select-level').val() || $form.find('select[name="level"]').val() || 'M';
        var size = parseInt($form.find('.select-size').val() || $form.find('select[name="size"]').val() || 300, 10);
        var roundBlock = $form.find('.select-round-block').val() || $form.find('select[name="round_block"]').val() || 'margin';
        var scale = parseInt($form.find('.input-logo-scale').val() || $form.find('input[name="logo_scale"]').val() || 20, 10);
        var preset = $form.find('.input-preset-logo').val() || '';
        var logoShape = $form.find('.select-logo-shape').val() || 'circle';

        var gradientType = $form.find('.select-gradient-type').val() || 'none';
        var rawGColor = $form.find('.input-gradient-color').val() || '#007bff';
        var gradientColor = toValidColor(rawGColor, '#007bff');
        var gradientAngle = parseInt($form.find('.input-gradient-angle').val() || 45, 10);
        var gradientBalance = parseInt($form.find('.input-gradient-balance').val() || 50, 10);
        var gradientDirection = $form.find('.select-gradient-direction').val() || 'vertical';

        var customEyesEnabled = $form.find('.toggle-custom-eyes').is(':checked');
        var eyeOuter = customEyesEnabled ? toValidColor($form.find('.input-eye-outer').val(), fg) : '';
        var eyeInner = customEyesEnabled ? toValidColor($form.find('.input-eye-inner').val(), fg) : '';

        // Extract QR payload based on form fields
        var payload = 'https://example.com';
        var activeTab = $('.tab-content .tab-pane.active').attr('id');

        if ($form.attr('id') === 'dynamic_form') {
            payload = $form.find('input[name="link"]').val() || 'https://example.com/dynamic-preview';
        } else if (activeTab === 'text') {
            payload = $form.find('textarea[name="text"]').val() || 'Sample Text Preview';
        } else if (activeTab === 'email') {
            var email = $form.find('input[name="email"]').val() || 'user@example.com';
            var sub = $form.find('input[name="subject"]').val() || '';
            var body = $form.find('textarea[name="message"]').val() || '';
            payload = 'MATMSG:TO:' + email + ';SUB:' + sub + ';BODY:' + body + ';;';
        } else if (activeTab === 'phone') {
            var cc = $form.find('select[name="country_code"]').val() || '+1';
            var num = $form.find('input[name="phone_number"]').val() || '5550199';
            payload = 'TEL:' + cc + num;
        } else if (activeTab === 'sms') {
            var cc = $form.find('select[name="country_code"]').val() || '+1';
            var num = $form.find('input[name="phone_number"]').val() || '5550199';
            var msg = $form.find('textarea[name="message"]').val() || '';
            payload = 'SMSTO:' + cc + num + ':' + msg;
        } else if (activeTab === 'whatsapp') {
            var cc = ($form.find('select[name="country_code"]').val() || '').replace('+', '');
            var num = $form.find('input[name="phone_number"]').val() || '';
            var msg = $form.find('textarea[name="message"]').val() || '';
            payload = 'https://wa.me/' + cc + num + (msg ? '?text=' + encodeURIComponent(msg) : '');
        } else if (activeTab === 'telegram') {
            var user = ($form.find('input[name="username"]').val() || 'telegram_user').replace('@', '');
            var msg = $form.find('textarea[name="message"]').val() || '';
            payload = 'https://t.me/' + user + (msg ? '?text=' + encodeURIComponent(msg) : '');
        } else if (activeTab === 'social') {
            var plat = $form.find('select[name="platform"]').val() || 'instagram';
            var usr = ($form.find('input[name="username"]').val() || 'username').replace('@', '');
            var curl = $form.find('input[name="custom_url"]').val() || '';
            if (curl) payload = curl;
            else if (plat === 'youtube') payload = 'https://youtube.com/@' + usr;
            else if (plat === 'instagram') payload = 'https://instagram.com/' + usr;
            else if (plat === 'twitter') payload = 'https://x.com/' + usr;
            else if (plat === 'linkedin') payload = 'https://linkedin.com/in/' + usr;
            else if (plat === 'tiktok') payload = 'https://tiktok.com/@' + usr;
        } else if (activeTab === 'wifi') {
            var ssid = $form.find('input[name="ssid"]').val() || 'MyNetwork';
            var pwd = $form.find('input[name="password"]').val() || '';
            var enc = $form.find('select[name="encryption"]').val() || 'WPA';
            payload = 'WIFI:T:' + enc + ';S:' + ssid + ';P:' + pwd + ';;';
        } else if (activeTab === 'crypto') {
            var cur = $form.find('select[name="currency"]').val() || 'ethereum';
            var addr = $form.find('input[name="address"]').val() || '0x71C...';
            payload = cur + ':' + addr;
        } else if (activeTab === 'sepa') {
            var iban = ($form.find('input[name="iban"]').val() || 'DE89370400440532013000').replace(/\s+/g, '');
            var name = $form.find('input[name="name"]').val() || 'Beneficiary';
            payload = 'BCD\n002\n1\nSCT\n\n' + name + '\n' + iban + '\nEUR10.00\n\nInvoice';
        } else if (activeTab === 'pix') {
            var pixk = $form.find('input[name="pix_key"]').val() || 'pix@example.com';
            payload = '00020126360014br.gov.bcb.pix0114' + pixk + '5204000053039865802BR5911RECEIVER6009SAOPAULO62070503***6304';
        } else if (activeTab === 'meeting') {
            var mid = $form.find('input[name="meeting_id"]').val() || '123456789';
            var murl = $form.find('input[name="url"]').val() || '';
            payload = murl || ('https://zoom.us/j/' + mid);
        } else if (activeTab === 'mecard') {
            var mname = $form.find('input[name="name"]').val() || 'Doe, John';
            payload = 'MECARD:N:' + mname + ';;';
        } else if (activeTab === 'medical') {
            var medName = $form.find('input[name="name"]').val() || 'Emergency Contact';
            payload = 'EMERGENCY MEDICAL ID\nName: ' + medName;
        } else if (activeTab === 'bookmark') {
            payload = $form.find('input[name="url"]').val() || 'https://example.com';
        } else if (activeTab === 'bitcoin') {
            var btcAddr = $form.find('input[name="address"]').val() || '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa';
            payload = 'bitcoin:' + btcAddr;
        } else {
            var firstInput = $form.find('input[type="text"]:visible, input[type="url"]:visible, textarea:visible').not('.input-foreground, .input-background, .input-filename, .input-gradient-color, .input-eye-outer, .input-eye-inner').first();
            if (firstInput.length && firstInput.val()) {
                payload = firstInput.val();
            }
        }

        return {
            foreground: fg,
            background: bg,
            level: level,
            size: size,
            roundBlock: roundBlock,
            logoScale: scale,
            logoShape: logoShape,
            presetLogo: preset,
            gradientType: gradientType,
            gradientColor: gradientColor,
            gradientAngle: gradientAngle,
            gradientBalance: gradientBalance,
            gradientDirection: gradientDirection,
            customEyesEnabled: customEyesEnabled,
            eyeOuter: eyeOuter,
            eyeInner: eyeInner,
            payload: payload
        };
    }

    // Helper to determine whether a module is in outer eye, inner eye, or body
    function getModuleEyeRole(r, c, N) {
        var isTL = (r < 7 && c < 7);
        var isTR = (r < 7 && c >= N - 7);
        var isBL = (r >= N - 7 && c < 7);
        if (!isTL && !isTR && !isBL) return 'body';

        var lr = (r < 7) ? r : (r - (N - 7));
        var lc = (c < 7) ? c : (c - (N - 7));

        if (lr === 0 || lr === 6 || lc === 0 || lc === 6) return 'outer';
        if (lr >= 2 && lr <= 4 && lc >= 2 && lc <= 4) return 'inner';
        return 'body';
    }

    function renderCanvas() {
        var canvas = document.getElementById('qr-preview-canvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var data = extractFormData();

        // Update live info labels
        $('#preview-size-label').text(data.size + ' x ' + data.size + ' px');
        var eccName = { L: 'Low (7%)', M: 'Medium (15%)', Q: 'High (25%)', H: 'Best (30%)' };
        $('#preview-ecc-label').text(eccName[data.level] || data.level);

        if (data.gradientType !== 'none') {
            $('#preview-fg-hex').text(data.foreground + ' → ' + data.gradientColor + ' (' + data.gradientAngle + '°, ' + data.gradientBalance + '%)');
            $('#preview-fg-swatch').css('background', 'linear-gradient(' + data.gradientAngle + 'deg, ' + data.foreground + ', ' + data.gradientColor + ')');
        } else {
            $('#preview-fg-hex').text(data.foreground);
            $('#preview-fg-swatch').css('background', data.foreground);
        }

        $('#preview-bg-hex').text(data.background);
        $('#preview-bg-swatch').css('background', data.background);
        var blockStyleNames = {
            margin: 'Square (Standard)',
            shrink: 'Dots / Circles',
            dots: 'Dots / Circles',
            rounded: 'Rounded Modules',
            none: 'Square (Compact)',
            enlarge: 'Square (Enlarged)'
        };
        $('#preview-block-label').text(blockStyleNames[data.roundBlock] || 'Square (Standard)');

        if ($('#dynamic_form').length) {
            $('#preview-data-label').html('<span class="badge badge-warning text-dark mr-1">Target</span> ' + $('<div>').text(data.payload).html());
        } else {
            $('#preview-data-label').text(data.payload);
        }

        var emblemLabel = 'None';
        var shapeName = { circle: 'Round', rounded: 'Rounded', square: 'Square', none: 'Transparent' }[data.logoShape] || 'Round';
        if (customLogoDataUrl) {
            emblemLabel = 'Custom File (' + shapeName + ', ' + data.logoScale + '%)';
        } else if (presetLogoName) {
            emblemLabel = presetLogoName.charAt(0).toUpperCase() + presetLogoName.slice(1) + ' (' + shapeName + ', ' + data.logoScale + '%)';
        }
        $('#preview-logo-label').text(emblemLabel);

        // Map ECC level (elevate if emblem is present)
        var eccMapping = {
            L: QRErrorCorrectLevel.L,
            M: QRErrorCorrectLevel.M,
            Q: QRErrorCorrectLevel.Q,
            H: QRErrorCorrectLevel.H
        };
        var ecc = eccMapping[data.level] !== undefined ? eccMapping[data.level] : QRErrorCorrectLevel.M;
        if (customLogoDataUrl || presetLogoName) {
            ecc = QRErrorCorrectLevel.H;
        }

        // Generate matrix
        var qr;
        try {
            qr = new QRCodeModel(0, ecc);
            qr.addData(data.payload);
            qr.make();
        } catch (e) {
            try {
                qr = new QRCodeModel(4, QRErrorCorrectLevel.L);
                qr.addData(data.payload.substring(0, 100));
                qr.make();
            } catch (err) {
                return;
            }
        }

        var moduleCount = qr.getModuleCount();
        var canvasSize = 240; // High resolution display
        canvas.width = canvasSize;
        canvas.height = canvasSize;

        // Background
        ctx.fillStyle = data.background;
        ctx.fillRect(0, 0, canvasSize, canvasSize);

        var margin = 14;
        var drawSize = canvasSize - (margin * 2);
        var moduleSize = drawSize / moduleCount;

        // Compute Body Gradient or Solid FillStyle
        var bodyFillStyle = data.foreground;
        if (data.gradientType === 'linear') {
            try {
                var rad = (data.gradientAngle) * (Math.PI / 180);
                var ux = Math.cos(rad);
                var uy = Math.sin(rad);
                var L = (drawSize / 2) * (Math.abs(ux) + Math.abs(uy));
                var cx = margin + (drawSize / 2);
                var cy = margin + (drawSize / 2);
                var x0 = cx - ux * L;
                var y0 = cy - uy * L;
                var x1 = cx + ux * L;
                var y1 = cy + uy * L;

                var grad = ctx.createLinearGradient(x0, y0, x1, y1);
                var tMid = Math.max(0.1, Math.min(0.9, data.gradientBalance / 100));

                var c1 = hexToRgb(data.foreground, { r: 0, g: 0, b: 0 });
                var c2 = hexToRgb(data.gradientColor, { r: 0, g: 123, b: 255 });
                var midR = Math.round((c1.r + c2.r) / 2);
                var midG = Math.round((c1.g + c2.g) / 2);
                var midB = Math.round((c1.b + c2.b) / 2);
                var midColor = 'rgb(' + midR + ',' + midG + ',' + midB + ')';

                grad.addColorStop(0, data.foreground);
                grad.addColorStop(tMid, midColor);
                grad.addColorStop(1, data.gradientColor);
                bodyFillStyle = grad;
            } catch (e) {
                bodyFillStyle = data.foreground;
            }
        } else if (data.gradientType === 'radial') {
            try {
                var cx = margin + (drawSize / 2);
                var cy = margin + (drawSize / 2);
                var rGrad = ctx.createRadialGradient(cx, cy, 0, cx, cy, drawSize / 1.3);
                var tMid = Math.max(0.1, Math.min(0.9, data.gradientBalance / 100));

                var c1 = hexToRgb(data.foreground, { r: 0, g: 0, b: 0 });
                var c2 = hexToRgb(data.gradientColor, { r: 0, g: 123, b: 255 });
                var midR = Math.round((c1.r + c2.r) / 2);
                var midG = Math.round((c1.g + c2.g) / 2);
                var midB = Math.round((c1.b + c2.b) / 2);
                var midColor = 'rgb(' + midR + ',' + midG + ',' + midB + ')';

                rGrad.addColorStop(0, data.foreground);
                rGrad.addColorStop(tMid, midColor);
                rGrad.addColorStop(1, data.gradientColor);
                bodyFillStyle = rGrad;
            } catch (e) {
                bodyFillStyle = data.foreground;
            }
        }

        // Draw Modules with Eye Role Coloring
        for (var row = 0; row < moduleCount; row++) {
            for (var col = 0; col < moduleCount; col++) {
                if (qr.isDark(row, col)) {
                    var x = margin + col * moduleSize;
                    var y = margin + row * moduleSize;

                    if (data.customEyesEnabled) {
                        var eyeRole = getModuleEyeRole(row, col, moduleCount);
                        if (eyeRole === 'outer' && data.eyeOuter) {
                            ctx.fillStyle = data.eyeOuter;
                        } else if (eyeRole === 'inner' && data.eyeInner) {
                            ctx.fillStyle = data.eyeInner;
                        } else {
                            ctx.fillStyle = bodyFillStyle;
                        }
                    } else {
                        ctx.fillStyle = bodyFillStyle;
                    }

                    if (data.roundBlock === 'shrink' || data.roundBlock === 'dots') {
                        var dotRadius = moduleSize * 0.42;
                        ctx.beginPath();
                        ctx.arc(x + moduleSize / 2, y + moduleSize / 2, dotRadius, 0, Math.PI * 2);
                        ctx.fill();
                    } else if (data.roundBlock === 'rounded') {
                        var rad = Math.max(2, Math.round(moduleSize * 0.28));
                        if (ctx.roundRect) {
                            ctx.beginPath();
                            ctx.roundRect(x + 0.3, y + 0.3, moduleSize - 0.6, moduleSize - 0.6, rad);
                            ctx.fill();
                        } else {
                            ctx.fillRect(x, y, moduleSize, moduleSize);
                        }
                    } else if (data.roundBlock === 'enlarge') {
                        ctx.fillRect(x - 0.3, y - 0.3, moduleSize + 0.6, moduleSize + 0.6);
                    } else {
                        // Margin / crisp / standard square
                        ctx.fillRect(x, y, moduleSize, moduleSize);
                    }
                }
            }
        }

        // Draw Center Emblem / Logo if active
        var logoSource = customLogoDataUrl || (presetLogoName ? 'dist/img/presets/' + presetLogoName + '.svg?v=' + (window.__qrAssetVersion || Date.now()) : null);
        if (logoSource) {
            var logoImg = new Image();
            logoImg.crossOrigin = 'anonymous';
            logoImg.onload = function () {
                var logoPixelSize = Math.round(canvasSize * (data.logoScale / 100));
                var logoX = (canvasSize - logoPixelSize) / 2;
                var logoY = (canvasSize - logoPixelSize) / 2;
                var punchoutPadding = Math.max(4, Math.round(moduleSize * 0.8));

                // Punchout Shape Background
                if (data.logoShape !== 'none') {
                    ctx.fillStyle = data.background;
                    ctx.beginPath();

                    if (data.logoShape === 'circle') {
                        var radius = (logoPixelSize / 2) + punchoutPadding;
                        ctx.arc(canvasSize / 2, canvasSize / 2, radius, 0, Math.PI * 2);
                        ctx.fill();
                    } else if (data.logoShape === 'rounded') {
                        var punchX = logoX - punchoutPadding;
                        var punchY = logoY - punchoutPadding;
                        var punchDim = logoPixelSize + (punchoutPadding * 2);
                        if (ctx.roundRect) {
                            ctx.roundRect(punchX, punchY, punchDim, punchDim, 8);
                        } else {
                            ctx.fillRect(punchX, punchY, punchDim, punchDim);
                        }
                        ctx.fill();
                    } else if (data.logoShape === 'square') {
                        var punchX = logoX - punchoutPadding;
                        var punchY = logoY - punchoutPadding;
                        var punchDim = logoPixelSize + (punchoutPadding * 2);
                        ctx.fillRect(punchX, punchY, punchDim, punchDim);
                    }
                }

                // Draw Emblem Image
                ctx.drawImage(logoImg, logoX, logoY, logoPixelSize, logoPixelSize);
            };
            logoImg.src = logoSource;
        }
    }

    function scheduleRender() {
        if (renderTimer) clearTimeout(renderTimer);
        renderTimer = setTimeout(renderCanvas, 60);
    }

    // Toggle gradient & eye marker subfields visibility
    window.updateQrGradientVisibility = function () {
        $('.select-gradient-type').each(function () {
            var $select = $(this);
            var $card = $select.closest('.card-body');
            var val = $select.val();
            if (val === 'linear') {
                $card.find('.gradient-color-group').show();
                $card.find('.gradient-rotation-group').show();
                $card.find('.gradient-balance-group').show();
                $card.find('.gradient-direction-group').hide();
            } else if (val === 'radial') {
                $card.find('.gradient-color-group').show();
                $card.find('.gradient-rotation-group').hide();
                $card.find('.gradient-balance-group').show();
                $card.find('.gradient-direction-group').hide();
            } else {
                $card.find('.gradient-color-group').hide();
                $card.find('.gradient-rotation-group').hide();
                $card.find('.gradient-balance-group').hide();
                $card.find('.gradient-direction-group').hide();
            }

            if ($.fn.colorpicker) {
                $card.find('.gradient-color-group .my-colorpicker2').each(function () {
                    try { $(this).colorpicker('destroy'); } catch(e){}
                    try { $(this).colorpicker(); } catch(e){}
                });
            }
        });

        $('.toggle-custom-eyes').each(function () {
            var $chk = $(this);
            var $card = $chk.closest('.card-body');
            if ($chk.is(':checked')) {
                $card.find('.custom-eyes-group').css('display', 'flex');
                if ($.fn.colorpicker) {
                    $card.find('.custom-eyes-group .my-colorpicker2').each(function () {
                        try { $(this).colorpicker('destroy'); } catch(e){}
                        try { $(this).colorpicker(); } catch(e){}
                    });
                }
            } else {
                $card.find('.custom-eyes-group').hide();
            }
        });

        if (typeof scheduleRender === 'function') {
            scheduleRender();
        }
    };

    // Bind event listeners
    $(document).ready(function () {
        if (!$('#qr-preview-canvas').length) return;

        // Custom File Input
        $(document).on('change', '.input-logo-file', function (e) {
            var file = e.target.files && e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    customLogoDataUrl = ev.target.result;
                    presetLogoName = '';
                    $('.btn-preset-logo').removeClass('active');
                    $('.btn-preset-logo[data-preset=""]').addClass('active');
                    $('.input-preset-logo').val('');
                    $('.select-level').val('H');
                    scheduleRender();
                };
                reader.readAsDataURL(file);
                $(this).next('.custom-file-label').text(file.name);
            }
        });

        // Preset Logo Buttons
        $(document).on('click', '.btn-preset-logo', function (e) {
            e.preventDefault();
            var preset = $(this).attr('data-preset');
            var $group = $(this).closest('.preset-logo-group');
            $group.find('.btn-preset-logo').removeClass('active');
            $(this).addClass('active');

            var $form = $(this).closest('form');
            $form.find('.input-preset-logo').val(preset);
            presetLogoName = preset;
            customLogoDataUrl = null;
            $form.find('.input-logo-file').val('');
            $form.find('.custom-file-label').text('Choose image...');

            if (preset) {
                $form.find('.select-level').val('H');
            }
            scheduleRender();
        });

        // Logo Scale Slider
        $(document).on('input change', '.input-logo-scale', function () {
            var val = $(this).val();
            $(this).closest('form').find('.logo-scale-value').text(val + '%');
            scheduleRender();
        });

        // Gradient Rotation Angle Slider
        $(document).on('input change', '.input-gradient-angle', function () {
            var val = $(this).val();
            var $card = $(this).closest('.card-body');
            $card.find('.gradient-angle-val').text(val + '°');
            $card.find('.btn-quick-angle').removeClass('active');
            $card.find('.btn-quick-angle[data-angle="' + val + '"]').addClass('active');
            scheduleRender();
        });

        // Gradient Quick Angle Buttons
        $(document).on('click', '.btn-quick-angle', function (e) {
            e.preventDefault();
            var angle = $(this).data('angle');
            var $card = $(this).closest('.card-body');
            $card.find('.input-gradient-angle').val(angle).trigger('change');
        });

        // Gradient Balance Slider
        $(document).on('input change', '.input-gradient-balance', function () {
            var val = $(this).val();
            var $card = $(this).closest('.card-body');
            $card.find('.gradient-balance-val').text(val + '%');
            scheduleRender();
        });

        // Clicking on any colorpicker text input also triggers the popup
        $(document).on('click focus', '.my-colorpicker2 input', function () {
            var $cp = $(this).closest('.my-colorpicker2');
            if ($cp.data('colorpicker')) {
                $cp.colorpicker('show');
            }
        });

        // Gradient & Custom Eyes switches
        $(document).on('change input', '.select-gradient-type, .toggle-custom-eyes', function () {
            window.updateQrGradientVisibility();
        });

        // Custom eye label click
        $(document).on('click', '.toggle-custom-eyes-label', function () {
            setTimeout(window.updateQrGradientVisibility, 20);
        });

        // Text input color changes: updates swatch
        $(document).on('input change', '.my-colorpicker2 input[type="text"]', function () {
            var col = ($(this).val() || '').trim();
            if (col && !col.startsWith('#') && /^[0-9a-fA-F]{3,8}$/.test(col)) {
                col = '#' + col;
            }
            if (col) {
                $(this).closest('.my-colorpicker2').find('.fa-square').css('color', col);
            }
            scheduleRender();
        });

        // Inputs & Selects generic listener
        $(document).on('input change keyup', 'input, textarea, select', function () {
            scheduleRender();
        });

        // Color picker changes - scoped to the changed container
        $(document).on('colorpickerChange', '.my-colorpicker2', function (event) {
            var colorVal = (event && event.color) ? event.color.toString() : $(this).find('input[type="text"]').val();
            $(this).find('input[type="text"]').val(colorVal);
            $(this).find('.fa-square').css('color', colorVal);
            scheduleRender();
        });

        // Tab changes
        $('a[data-toggle="pill"]').on('shown.bs.tab', function () {
            window.updateQrGradientVisibility();
            scheduleRender();
        });

        // Download Preview button
        $('#btn-download-preview').on('click', function () {
            var canvas = document.getElementById('qr-preview-canvas');
            if (!canvas) return;
            var dataUrl = canvas.toDataURL('image/png');
            var link = document.createElement('a');
            var fn = ($('.input-filename').val() || 'qr_preview').trim().replace(/[^a-zA-Z0-9_\-]/g, '_');
            link.download = fn + '_preview.png';
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Initial setup
        window.updateQrGradientVisibility();
        scheduleRender();
    });

})(window, document, jQuery);
