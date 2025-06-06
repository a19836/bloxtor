<?php 
class MimeTypeHandler {
	
	public static $types = array(
		array("mime_type" => "application/vnd.hzn-3d-crossword", "extension" => "x3d", "description" => "3D Crossword Plugin"),
		array("mime_type" => "video/3gpp", "extension" => "3gp", "description" => "3GP"),
		array("mime_type" => "video/3gpp2", "extension" => "3g2", "description" => "3GP2"),
		array("mime_type" => "application/vnd.mseq", "extension" => "mseq", "description" => "3GPP MSEQ File"),
		array("mime_type" => "application/vnd.3m.post-it-notes", "extension" => "pwn", "description" => "3M Post It Notes"),
		array("mime_type" => "application/vnd.3gpp.pic-bw-large", "extension" => "plb", "description" => "3rd Generation Partnership Project - Pic Large"),
		array("mime_type" => "application/vnd.3gpp.pic-bw-small", "extension" => "psb", "description" => "3rd Generation Partnership Project - Pic Small"),
		array("mime_type" => "application/vnd.3gpp.pic-bw-var", "extension" => "pvb", "description" => "3rd Generation Partnership Project - Pic Var"),
		array("mime_type" => "application/vnd.3gpp2.tcap", "extension" => "tcap", "description" => "3rd Generation Partnership Project - Transaction Capabilities Application Part"),
		array("mime_type" => "application/x-7z-compressed", "extension" => "7z", "description" => "7-Zip"),
		array("mime_type" => "application/x-abiword", "extension" => "abw", "description" => "AbiWord"),
		array("mime_type" => "application/x-ace-compressed", "extension" => "ace", "description" => "Ace Archive"),
		array("mime_type" => "application/vnd.americandynamics.acc", "extension" => "acc", "description" => "Active Content Compression"),
		array("mime_type" => "application/vnd.acucobol", "extension" => "acu", "description" => "ACU Cobol"),
		array("mime_type" => "application/vnd.acucorp", "extension" => "atc", "description" => "ACU Cobol"),
		array("mime_type" => "audio/adpcm", "extension" => "adp", "description" => "Adaptive differential pulse-code modulation"),
		array("mime_type" => "application/x-authorware-bin", "extension" => "aab", "description" => "Adobe (Macropedia) Authorware - Binary File"),
		array("mime_type" => "application/x-authorware-map", "extension" => "aam", "description" => "Adobe (Macropedia) Authorware - Map"),
		array("mime_type" => "application/x-authorware-seg", "extension" => "aas", "description" => "Adobe (Macropedia) Authorware - Segment File"),
		array("mime_type" => "application/vnd.adobe.air-application-installer-package+zip", "extension" => "air", "description" => "Adobe AIR Application"),
		array("mime_type" => "application/x-shockwave-flash", "extension" => "swf", "description" => "Adobe Flash"),
		array("mime_type" => "application/vnd.adobe.fxp", "extension" => "fxp", "description" => "Adobe Flex Project"),
		array("mime_type" => "application/pdf", "extension" => "pdf", "description" => "Adobe Portable Document Format"),
		array("mime_type" => "application/vnd.cups-ppd", "extension" => "ppd", "description" => "Adobe PostScript Printer Description File Format"),
		array("mime_type" => "application/x-director", "extension" => "dir", "description" => "Adobe Shockwave Player"),
		array("mime_type" => "application/vnd.adobe.xdp+xml", "extension" => "xdp", "description" => "Adobe XML Data Package"),
		array("mime_type" => "application/vnd.adobe.xfdf", "extension" => "xfdf", "description" => "Adobe XML Forms Data Format"),
		array("mime_type" => "audio/x-aac", "extension" => "aac", "description" => "Advanced Audio Coding (AAC)"),
		array("mime_type" => "application/vnd.ahead.space", "extension" => "ahead", "description" => "Ahead AIR Application"),
		array("mime_type" => "application/vnd.airzip.filesecure.azf", "extension" => "azf", "description" => "AirZip FileSECURE"),
		array("mime_type" => "application/vnd.airzip.filesecure.azs", "extension" => "azs", "description" => "AirZip FileSECURE"),
		array("mime_type" => "application/vnd.amazon.ebook", "extension" => "azw", "description" => "Amazon Kindle eBook format"),
		array("mime_type" => "application/vnd.amiga.ami", "extension" => "ami", "description" => "AmigaDE"),
		array("mime_type" => "application/andrew-inset", "extension" => "N/A", "description" => "Andrew Toolkit"),
		array("mime_type" => "application/vnd.android.package-archive", "extension" => "apk", "description" => "Android Package Archive"),
		array("mime_type" => "application/vnd.anser-web-certificate-issue-initiation", "extension" => "cii", "description" => "ANSER-WEB Terminal Client - Certificate Issue"),
		array("mime_type" => "application/vnd.anser-web-funds-transfer-initiation", "extension" => "fti", "description" => "ANSER-WEB Terminal Client - Web Funds Transfer"),
		array("mime_type" => "application/vnd.antix.game-component", "extension" => "atx", "description" => "Antix Game Player"),
		array("mime_type" => "application/vnd.apple.installer+xml", "extension" => "mpkg", "description" => "Apple Installer Package"),
		array("mime_type" => "application/applixware", "extension" => "aw", "description" => "Applixware"),
		array("mime_type" => "application/vnd.hhe.lesson-player", "extension" => "les", "description" => "Archipelago Lesson Player"),
		array("mime_type" => "application/vnd.aristanetworks.swi", "extension" => "swi", "description" => "Arista Networks Software Image"),
		array("mime_type" => "text/x-asm", "extension" => "s", "description" => "Assembler Source File"),
		array("mime_type" => "application/atomcat+xml", "extension" => "atomcat", "description" => "Atom Publishing Protocol"),
		array("mime_type" => "application/atomsvc+xml", "extension" => "atomsvc", "description" => "Atom Publishing Protocol Service Document"),
		array("mime_type" => "application/atom+xml", "extension" => "atom, xml", "description" => "Atom Syndication Format"),
		array("mime_type" => "application/pkix-attr-cert", "extension" => "ac", "description" => "Attribute Certificate"),
		array("mime_type" => "audio/x-aiff", "extension" => "aif", "description" => "Audio Interchange File Format"),
		array("mime_type" => "video/x-msvideo", "extension" => "avi", "description" => "Audio Video Interleave (AVI)"),
		array("mime_type" => "application/vnd.audiograph", "extension" => "aep", "description" => "Audiograph"),
		array("mime_type" => "image/vnd.dxf", "extension" => "dxf", "description" => "AutoCAD DXF"),
		array("mime_type" => "model/vnd.dwf", "extension" => "dwf", "description" => "Autodesk Design Web Format (DWF)"),
		array("mime_type" => "text/plain-bas", "extension" => "par", "description" => "BAS Partitur Format"),
		array("mime_type" => "application/x-bcpio", "extension" => "bcpio", "description" => "Binary CPIO Archive"),
		array("mime_type" => "application/octet-stream", "extension" => "bin", "description" => "Binary Data"),
		array("mime_type" => "image/bmp", "extension" => "bmp", "description" => "Bitmap Image File"),
		array("mime_type" => "application/x-bittorrent", "extension" => "torrent", "description" => "BitTorrent"),
		array("mime_type" => "application/vnd.rim.cod", "extension" => "cod", "description" => "Blackberry COD File"),
		array("mime_type" => "application/vnd.blueice.multipass", "extension" => "mpm", "description" => "Blueice Research Multipass"),
		array("mime_type" => "application/vnd.bmi", "extension" => "bmi", "description" => "BMI Drawing Data Interchange"),
		array("mime_type" => "application/x-sh", "extension" => "sh", "description" => "Bourne Shell Script"),
		array("mime_type" => "image/prs.btif", "extension" => "btif", "description" => "BTIF"),
		array("mime_type" => "application/vnd.businessobjects", "extension" => "rep", "description" => "BusinessObjects"),
		array("mime_type" => "application/x-bzip", "extension" => "bz", "description" => "Bzip Archive"),
		array("mime_type" => "application/x-bzip2", "extension" => "bz2", "description" => "Bzip2 Archive"),
		array("mime_type" => "application/x-csh", "extension" => "csh", "description" => "C Shell Script"),
		array("mime_type" => "text/x-c", "extension" => "c", "description" => "C Source File"),
		array("mime_type" => "application/vnd.chemdraw+xml", "extension" => "cdxml", "description" => "CambridgeSoft Chem Draw"),
		array("mime_type" => "text/css", "extension" => "css", "description" => "Cascading Style Sheets (CSS)"),
		array("mime_type" => "chemical/x-cdx", "extension" => "cdx", "description" => "ChemDraw eXchange file"),
		array("mime_type" => "chemical/x-cml", "extension" => "cml", "description" => "Chemical Markup Language"),
		array("mime_type" => "chemical/x-csml", "extension" => "csml", "description" => "Chemical Style Markup Language"),
		array("mime_type" => "application/vnd.contact.cmsg", "extension" => "cdbcmsg", "description" => "CIM Database"),
		array("mime_type" => "application/vnd.claymore", "extension" => "cla", "description" => "Claymore Data Files"),
		array("mime_type" => "application/vnd.clonk.c4group", "extension" => "c4g", "description" => "Clonk Game"),
		array("mime_type" => "image/vnd.dvb.subtitle", "extension" => "sub", "description" => "Close Captioning - Subtitle"),
		array("mime_type" => "application/cdmi-capability", "extension" => "cdmia", "description" => "Cloud Data Management Interface (CDMI) - Capability"),
		array("mime_type" => "application/cdmi-container", "extension" => "cdmic", "description" => "Cloud Data Management Interface (CDMI) - Contaimer"),
		array("mime_type" => "application/cdmi-domain", "extension" => "cdmid", "description" => "Cloud Data Management Interface (CDMI) - Domain"),
		array("mime_type" => "application/cdmi-object", "extension" => "cdmio", "description" => "Cloud Data Management Interface (CDMI) - Object"),
		array("mime_type" => "application/cdmi-queue", "extension" => "cdmiq", "description" => "Cloud Data Management Interface (CDMI) - Queue"),
		array("mime_type" => "application/vnd.cluetrust.cartomobile-config", "extension" => "c11amc", "description" => "ClueTrust CartoMobile - Config"),
		array("mime_type" => "application/vnd.cluetrust.cartomobile-config-pkg", "extension" => "c11amz", "description" => "ClueTrust CartoMobile - Config Package"),
		array("mime_type" => "image/x-cmu-raster", "extension" => "ras", "description" => "CMU Image"),
		array("mime_type" => "model/vnd.collada+xml", "extension" => "dae", "description" => "COLLADA"),
		array("mime_type" => "text/csv", "extension" => "csv", "description" => "Comma-Seperated Values"),
		array("mime_type" => "application/mac-compactpro", "extension" => "cpt", "description" => "Compact Pro"),
		array("mime_type" => "application/vnd.wap.wmlc", "extension" => "wmlc", "description" => "Compiled Wireless Markup Language (WMLC)"),
		array("mime_type" => "image/cgm", "extension" => "cgm", "description" => "Computer Graphics Metafile"),
		array("mime_type" => "x-conference/x-cooltalk", "extension" => "ice", "description" => "CoolTalk"),
		array("mime_type" => "image/x-cmx", "extension" => "cmx", "description" => "Corel Metafile Exchange (CMX)"),
		array("mime_type" => "application/vnd.xara", "extension" => "xar", "description" => "CorelXARA"),
		array("mime_type" => "application/vnd.cosmocaller", "extension" => "cmc", "description" => "CosmoCaller"),
		array("mime_type" => "application/x-cpio", "extension" => "cpio", "description" => "CPIO Archive"),
		array("mime_type" => "application/vnd.crick.clicker", "extension" => "clkx", "description" => "CrickSoftware - Clicker"),
		array("mime_type" => "application/vnd.crick.clicker.keyboard", "extension" => "clkk", "description" => "CrickSoftware - Clicker - Keyboard"),
		array("mime_type" => "application/vnd.crick.clicker.palette", "extension" => "clkp", "description" => "CrickSoftware - Clicker - Palette"),
		array("mime_type" => "application/vnd.crick.clicker.template", "extension" => "clkt", "description" => "CrickSoftware - Clicker - Template"),
		array("mime_type" => "application/vnd.crick.clicker.wordbank", "extension" => "clkw", "description" => "CrickSoftware - Clicker - Wordbank"),
		array("mime_type" => "application/vnd.criticaltools.wbs+xml", "extension" => "wbs", "description" => "Critical Tools - PERT Chart EXPERT"),
		array("mime_type" => "application/vnd.rig.cryptonote", "extension" => "cryptonote", "description" => "CryptoNote"),
		array("mime_type" => "chemical/x-cif", "extension" => "cif", "description" => "Crystallographic Interchange Format"),
		array("mime_type" => "chemical/x-cmdf", "extension" => "cmdf", "description" => "CrystalMaker Data Format"),
		array("mime_type" => "application/cu-seeme", "extension" => "cu", "description" => "CU-SeeMe"),
		array("mime_type" => "application/prs.cww", "extension" => "cww", "description" => "CU-Writer"),
		array("mime_type" => "text/vnd.curl", "extension" => "curl", "description" => "Curl - Applet"),
		array("mime_type" => "text/vnd.curl.dcurl", "extension" => "dcurl", "description" => "Curl - Detached Applet"),
		array("mime_type" => "text/vnd.curl.mcurl", "extension" => "mcurl", "description" => "Curl - Manifest File"),
		array("mime_type" => "text/vnd.curl.scurl", "extension" => "scurl", "description" => "Curl - Source Code"),
		array("mime_type" => "application/vnd.curl.car", "extension" => "car", "description" => "CURL Applet"),
		array("mime_type" => "application/vnd.curl.pcurl", "extension" => "pcurl", "description" => "CURL Applet"),
		array("mime_type" => "application/vnd.yellowriver-custom-menu", "extension" => "cmp", "description" => "CustomMenu"),
		array("mime_type" => "application/dssc+der", "extension" => "dssc", "description" => "Data Structure for the Security Suitability of Cryptographic Algorithms"),
		array("mime_type" => "application/dssc+xml", "extension" => "xdssc", "description" => "Data Structure for the Security Suitability of Cryptographic Algorithms"),
		array("mime_type" => "application/x-debian-package", "extension" => "deb", "description" => "Debian Package"),
		array("mime_type" => "audio/vnd.dece.audio", "extension" => "uva", "description" => "DECE Audio"),
		array("mime_type" => "image/vnd.dece.graphic", "extension" => "uvi", "description" => "DECE Graphic"),
		array("mime_type" => "video/vnd.dece.hd", "extension" => "uvh", "description" => "DECE High Definition Video"),
		array("mime_type" => "video/vnd.dece.mobile", "extension" => "uvm", "description" => "DECE Mobile Video"),
		array("mime_type" => "video/vnd.uvvu.mp4", "extension" => "uvu", "description" => "DECE MP4"),
		array("mime_type" => "video/vnd.dece.pd", "extension" => "uvp", "description" => "DECE PD Video"),
		array("mime_type" => "video/vnd.dece.sd", "extension" => "uvs", "description" => "DECE SD Video"),
		array("mime_type" => "video/vnd.dece.video", "extension" => "uvv", "description" => "DECE Video"),
		array("mime_type" => "application/x-dvi", "extension" => "dvi", "description" => "Device Independent File Format (DVI)"),
		array("mime_type" => "application/vnd.fdsn.seed", "extension" => "seed", "description" => "Digital Siesmograph Networks - SEED Datafiles"),
		array("mime_type" => "application/x-dtbook+xml", "extension" => "dtb", "description" => "Digital Talking Book"),
		array("mime_type" => "application/x-dtbresource+xml", "extension" => "res", "description" => "Digital Talking Book - Resource File"),
		array("mime_type" => "application/vnd.dvb.ait", "extension" => "ait", "description" => "Digital Video Broadcasting"),
		array("mime_type" => "application/vnd.dvb.service", "extension" => "svc", "description" => "Digital Video Broadcasting"),
		array("mime_type" => "audio/vnd.digital-winds", "extension" => "eol", "description" => "Digital Winds Music"),
		array("mime_type" => "image/vnd.djvu", "extension" => "djvu", "description" => "DjVu"),
		array("mime_type" => "application/xml-dtd", "extension" => "dtd", "description" => "Document Type Definition"),
		array("mime_type" => "application/vnd.dolby.mlp", "extension" => "mlp", "description" => "Dolby Meridian Lossless Packing"),
		array("mime_type" => "application/x-doom", "extension" => "wad", "description" => "Doom Video Game"),
		array("mime_type" => "application/vnd.dpgraph", "extension" => "dpg", "description" => "DPGraph"),
		array("mime_type" => "audio/vnd.dra", "extension" => "dra", "description" => "DRA Audio"),
		array("mime_type" => "application/vnd.dreamfactory", "extension" => "dfac", "description" => "DreamFactory"),
		array("mime_type" => "audio/vnd.dts", "extension" => "dts", "description" => "DTS Audio"),
		array("mime_type" => "audio/vnd.dts.hd", "extension" => "dtshd", "description" => "DTS High Definition Audio"),
		array("mime_type" => "image/vnd.dwg", "extension" => "dwg", "description" => "DWG Drawing"),
		array("mime_type" => "application/vnd.dynageo", "extension" => "geo", "description" => "DynaGeo"),
		array("mime_type" => "application/ecmascript", "extension" => "es", "description" => "ECMAScript"),
		array("mime_type" => "application/vnd.ecowin.chart", "extension" => "mag", "description" => "EcoWin Chart"),
		array("mime_type" => "image/vnd.fujixerox.edmics-mmr", "extension" => "mmr", "description" => "EDMICS 2000"),
		array("mime_type" => "image/vnd.fujixerox.edmics-rlc", "extension" => "rlc", "description" => "EDMICS 2000"),
		array("mime_type" => "application/exi", "extension" => "exi", "description" => "Efficient XML Interchange"),
		array("mime_type" => "application/vnd.proteus.magazine", "extension" => "mgz", "description" => "EFI Proteus"),
		array("mime_type" => "application/epub+zip", "extension" => "epub", "description" => "Electronic Publication"),
		array("mime_type" => "message/rfc822", "extension" => "eml", "description" => "Email Message"),
		array("mime_type" => "application/vnd.enliven", "extension" => "nml", "description" => "Enliven Viewer"),
		array("mime_type" => "application/vnd.is-xpr", "extension" => "xpr", "description" => "Express by Infoseek"),
		array("mime_type" => "image/vnd.xiff", "extension" => "xif", "description" => "eXtended Image File Format (XIFF)"),
		array("mime_type" => "application/vnd.xfdl", "extension" => "xfdl", "description" => "Extensible Forms Description Language"),
		array("mime_type" => "application/emma+xml", "extension" => "emma", "description" => "Extensible MultiModal Annotation"),
		array("mime_type" => "application/vnd.ezpix-album", "extension" => "ez2", "description" => "EZPix Secure Photo Album"),
		array("mime_type" => "application/vnd.ezpix-package", "extension" => "ez3", "description" => "EZPix Secure Photo Album"),
		array("mime_type" => "image/vnd.fst", "extension" => "fst", "description" => "FAST Search & Transfer ASA"),
		array("mime_type" => "video/vnd.fvt", "extension" => "fvt", "description" => "FAST Search & Transfer ASA"),
		array("mime_type" => "image/vnd.fastbidsheet", "extension" => "fbs", "description" => "FastBid Sheet"),
		array("mime_type" => "application/vnd.denovo.fcselayout-link", "extension" => "fe_launch", "description" => "FCS Express Layout Link"),
		array("mime_type" => "video/x-f4v", "extension" => "f4v", "description" => "Flash Video"),
		array("mime_type" => "video/x-flv", "extension" => "flv", "description" => "Flash Video"),
		array("mime_type" => "image/vnd.fpx", "extension" => "fpx", "description" => "FlashPix"),
		array("mime_type" => "image/vnd.net-fpx", "extension" => "npx", "description" => "FlashPix"),
		array("mime_type" => "text/vnd.fmi.flexstor", "extension" => "flx", "description" => "FLEXSTOR"),
		array("mime_type" => "video/x-fli", "extension" => "fli", "description" => "FLI/FLC Animation Format"),
		array("mime_type" => "application/vnd.fluxtime.clip", "extension" => "ftc", "description" => "FluxTime Clip"),
		array("mime_type" => "application/vnd.fdf", "extension" => "fdf", "description" => "Forms Data Format"),
		array("mime_type" => "text/x-fortran", "extension" => "f", "description" => "Fortran Source File"),
		array("mime_type" => "application/vnd.mif", "extension" => "mif", "description" => "FrameMaker Interchange Format"),
		array("mime_type" => "application/vnd.framemaker", "extension" => "fm", "description" => "FrameMaker Normal Format"),
		array("mime_type" => "image/x-freehand", "extension" => "fh", "description" => "FreeHand MX"),
		array("mime_type" => "application/vnd.fsc.weblaunch", "extension" => "fsc", "description" => "Friendly Software Corporation"),
		array("mime_type" => "application/vnd.frogans.fnc", "extension" => "fnc", "description" => "Frogans Player"),
		array("mime_type" => "application/vnd.frogans.ltf", "extension" => "ltf", "description" => "Frogans Player"),
		array("mime_type" => "application/vnd.fujixerox.ddd", "extension" => "ddd", "description" => "Fujitsu - Xerox 2D CAD Data"),
		array("mime_type" => "application/vnd.fujixerox.docuworks", "extension" => "xdw", "description" => "Fujitsu - Xerox DocuWorks"),
		array("mime_type" => "application/vnd.fujixerox.docuworks.binder", "extension" => "xbd", "description" => "Fujitsu - Xerox DocuWorks Binder"),
		array("mime_type" => "application/vnd.fujitsu.oasys", "extension" => "oas", "description" => "Fujitsu Oasys"),
		array("mime_type" => "application/vnd.fujitsu.oasys2", "extension" => "oa2", "description" => "Fujitsu Oasys"),
		array("mime_type" => "application/vnd.fujitsu.oasys3", "extension" => "oa3", "description" => "Fujitsu Oasys"),
		array("mime_type" => "application/vnd.fujitsu.oasysgp", "extension" => "fg5", "description" => "Fujitsu Oasys"),
		array("mime_type" => "application/vnd.fujitsu.oasysprs", "extension" => "bh2", "description" => "Fujitsu Oasys"),
		array("mime_type" => "application/x-futuresplash", "extension" => "spl", "description" => "FutureSplash Animator"),
		array("mime_type" => "application/vnd.fuzzysheet", "extension" => "fzs", "description" => "FuzzySheet"),
		array("mime_type" => "image/g3fax", "extension" => "g3", "description" => "G3 Fax Image"),
		array("mime_type" => "application/vnd.gmx", "extension" => "gmx", "description" => "GameMaker ActiveX"),
		array("mime_type" => "model/vnd.gtw", "extension" => "gtw", "description" => "Gen-Trix Studio"),
		array("mime_type" => "application/vnd.genomatix.tuxedo", "extension" => "txd", "description" => "Genomatix Tuxedo Framework"),
		array("mime_type" => "application/vnd.geogebra.file", "extension" => "ggb", "description" => "GeoGebra"),
		array("mime_type" => "application/vnd.geogebra.tool", "extension" => "ggt", "description" => "GeoGebra"),
		array("mime_type" => "model/vnd.gdl", "extension" => "gdl", "description" => "Geometric Description Language (GDL)"),
		array("mime_type" => "application/vnd.geometry-explorer", "extension" => "gex", "description" => "GeoMetry Explorer"),
		array("mime_type" => "application/vnd.geonext", "extension" => "gxt", "description" => "GEONExT and JSXGraph"),
		array("mime_type" => "application/vnd.geoplan", "extension" => "g2w", "description" => "GeoplanW"),
		array("mime_type" => "application/vnd.geospace", "extension" => "g3w", "description" => "GeospacW"),
		array("mime_type" => "application/x-font-ghostscript", "extension" => "gsf", "description" => "Ghostscript Font"),
		array("mime_type" => "application/x-font-bdf", "extension" => "bdf", "description" => "Glyph Bitmap Distribution Format"),
		array("mime_type" => "application/x-gtar", "extension" => "gtar", "description" => "GNU Tar Files"),
		array("mime_type" => "application/x-texinfo", "extension" => "texinfo", "description" => "GNU Texinfo Document"),
		array("mime_type" => "application/x-gnumeric", "extension" => "gnumeric", "description" => "Gnumeric"),
		array("mime_type" => "application/vnd.google-earth.kml+xml", "extension" => "kml", "description" => "Google Earth - KML"),
		array("mime_type" => "application/vnd.google-earth.kmz", "extension" => "kmz", "description" => "Google Earth - Zipped KML"),
		array("mime_type" => "application/vnd.grafeq", "extension" => "gqf", "description" => "GrafEq"),
		array("mime_type" => "image/gif", "extension" => "gif", "description" => "Graphics Interchange Format"),
		array("mime_type" => "text/vnd.graphviz", "extension" => "gv", "description" => "Graphviz"),
		array("mime_type" => "application/vnd.groove-account", "extension" => "gac", "description" => "Groove - Account"),
		array("mime_type" => "application/vnd.groove-help", "extension" => "ghf", "description" => "Groove - Help"),
		array("mime_type" => "application/vnd.groove-identity-message", "extension" => "gim", "description" => "Groove - Identity Message"),
		array("mime_type" => "application/vnd.groove-injector", "extension" => "grv", "description" => "Groove - Injector"),
		array("mime_type" => "application/vnd.groove-tool-message", "extension" => "gtm", "description" => "Groove - Tool Message"),
		array("mime_type" => "application/vnd.groove-tool-template", "extension" => "tpl", "description" => "Groove - Tool Template"),
		array("mime_type" => "application/vnd.groove-vcard", "extension" => "vcg", "description" => "Groove - Vcard"),
		array("mime_type" => "video/h261", "extension" => "h261", "description" => "H.261"),
		array("mime_type" => "video/h263", "extension" => "h263", "description" => "H.263"),
		array("mime_type" => "video/h264", "extension" => "h264", "description" => "H.264"),
		array("mime_type" => "application/vnd.hp-hpid", "extension" => "hpid", "description" => "Hewlett Packard Instant Delivery"),
		array("mime_type" => "application/vnd.hp-hps", "extension" => "hps", "description" => "Hewlett-Packard's WebPrintSmart"),
		array("mime_type" => "application/x-hdf", "extension" => "hdf", "description" => "Hierarchical Data Format"),
		array("mime_type" => "audio/vnd.rip", "extension" => "rip", "description" => "Hit'n'Mix"),
		array("mime_type" => "application/vnd.hbci", "extension" => "hbci", "description" => "Homebanking Computer Interface (HBCI)"),
		array("mime_type" => "application/vnd.hp-jlyt", "extension" => "jlt", "description" => "HP Indigo Digital Press - Job Layout Languate"),
		array("mime_type" => "application/vnd.hp-pcl", "extension" => "pcl", "description" => "HP Printer Command Language"),
		array("mime_type" => "application/vnd.hp-hpgl", "extension" => "hpgl", "description" => "HP-GL/2 and HP RTL"),
		array("mime_type" => "application/vnd.yamaha.hv-script", "extension" => "hvs", "description" => "HV Script"),
		array("mime_type" => "application/vnd.yamaha.hv-dic", "extension" => "hvd", "description" => "HV Voice Dictionary"),
		array("mime_type" => "application/vnd.yamaha.hv-voice", "extension" => "hvp", "description" => "HV Voice Parameter"),
		array("mime_type" => "application/vnd.hydrostatix.sof-data", "extension" => "sfd-hdstx", "description" => "Hydrostatix Master Suite"),
		array("mime_type" => "application/hyperstudio", "extension" => "stk", "description" => "Hyperstudio"),
		array("mime_type" => "application/vnd.hal+xml", "extension" => "hal", "description" => "Hypertext Application Language"),
		array("mime_type" => "text/html", "extension" => "html", "description" => "HyperText Markup Language (HTML)"),
		array("mime_type" => "application/vnd.ibm.rights-management", "extension" => "irm", "description" => "IBM DB2 Rights Manager"),
		array("mime_type" => "application/vnd.ibm.secure-container", "extension" => "sc", "description" => "IBM Electronic Media Management System - Secure Container"),
		array("mime_type" => "text/calendar", "extension" => "ics", "description" => "iCalendar"),
		array("mime_type" => "application/vnd.iccprofile", "extension" => "icc", "description" => "ICC profile"),
		array("mime_type" => "image/x-icon", "extension" => "ico", "description" => "Icon Image"),
		array("mime_type" => "application/vnd.igloader", "extension" => "igl", "description" => "igLoader"),
		array("mime_type" => "image/ief", "extension" => "ief", "description" => "Image Exchange Format"),
		array("mime_type" => "application/vnd.immervision-ivp", "extension" => "ivp", "description" => "ImmerVision PURE Players"),
		array("mime_type" => "application/vnd.immervision-ivu", "extension" => "ivu", "description" => "ImmerVision PURE Players"),
		array("mime_type" => "application/reginfo+xml", "extension" => "rif", "description" => "IMS Networks"),
		array("mime_type" => "text/vnd.in3d.3dml", "extension" => "3dml", "description" => "In3D - 3DML"),
		array("mime_type" => "text/vnd.in3d.spot", "extension" => "spot", "description" => "In3D - 3DML"),
		array("mime_type" => "model/iges", "extension" => "igs", "description" => "Initial Graphics Exchange Specification (IGES)"),
		array("mime_type" => "application/vnd.intergeo", "extension" => "i2g", "description" => "Interactive Geometry Software"),
		array("mime_type" => "application/vnd.cinderella", "extension" => "cdy", "description" => "Interactive Geometry Software Cinderella"),
		array("mime_type" => "application/vnd.intercon.formnet", "extension" => "xpw", "description" => "Intercon FormNet"),
		array("mime_type" => "application/vnd.isac.fcs", "extension" => "fcs", "description" => "International Society for Advancement of Cytometry"),
		array("mime_type" => "application/ipfix", "extension" => "ipfix", "description" => "Internet Protocol Flow Information Export"),
		array("mime_type" => "application/pkix-cert", "extension" => "cer", "description" => "Internet Public Key Infrastructure - Certificate"),
		array("mime_type" => "application/pkixcmp", "extension" => "pki", "description" => "Internet Public Key Infrastructure - Certificate Management Protocole"),
		array("mime_type" => "application/pkix-crl", "extension" => "crl", "description" => "Internet Public Key Infrastructure - Certificate Revocation Lists"),
		array("mime_type" => "application/pkix-pkipath", "extension" => "pkipath", "description" => "Internet Public Key Infrastructure - Certification Path"),
		array("mime_type" => "application/vnd.insors.igm", "extension" => "igm", "description" => "IOCOM Visimeet"),
		array("mime_type" => "application/vnd.ipunplugged.rcprofile", "extension" => "rcprofile", "description" => "IP Unplugged Roaming Client"),
		array("mime_type" => "application/vnd.irepository.package+xml", "extension" => "irp", "description" => "iRepository / Lucidoc Editor"),
		array("mime_type" => "text/vnd.sun.j2me.app-descriptor", "extension" => "jad", "description" => "J2ME App Descriptor"),
		array("mime_type" => "application/java-archive", "extension" => "jar", "description" => "Java Archive"),
		array("mime_type" => "application/java-vm", "extension" => "class", "description" => "Java Bytecode File"),
		array("mime_type" => "application/x-java-jnlp-file", "extension" => "jnlp", "description" => "Java Network Launching Protocol"),
		array("mime_type" => "application/java-serialized-object", "extension" => "ser", "description" => "Java Serialized Object"),
		array("mime_type" => "text/x-java-source.java", "extension" => "java", "description" => "Java Source File"),
		array("mime_type" => "application/javascript", "extension" => "js", "description" => "JavaScript"),
		array("mime_type" => "application/json", "extension" => "json", "description" => "JavaScript Object Notation (JSON)"),
		array("mime_type" => "application/vnd.joost.joda-archive", "extension" => "joda", "description" => "Joda Archive"),
		array("mime_type" => "video/jpm", "extension" => "jpm", "description" => "JPEG 2000 Compound Image File Format"),
		array("mime_type" => "image/jpeg", "extension" => "jpeg, jpg", "description" => "JPEG Image"),
		array("mime_type" => "video/jpeg", "extension" => "jpgv", "description" => "JPGVideo"),
		array("mime_type" => "application/vnd.kahootz", "extension" => "ktz", "description" => "Kahootz"),
		array("mime_type" => "application/vnd.chipnuts.karaoke-mmd", "extension" => "mmd", "description" => "Karaoke on Chipnuts Chipsets"),
		array("mime_type" => "application/vnd.kde.karbon", "extension" => "karbon", "description" => "KDE KOffice Office Suite - Karbon"),
		array("mime_type" => "application/vnd.kde.kchart", "extension" => "chrt", "description" => "KDE KOffice Office Suite - KChart"),
		array("mime_type" => "application/vnd.kde.kformula", "extension" => "kfo", "description" => "KDE KOffice Office Suite - Kformula"),
		array("mime_type" => "application/vnd.kde.kivio", "extension" => "flw", "description" => "KDE KOffice Office Suite - Kivio"),
		array("mime_type" => "application/vnd.kde.kontour", "extension" => "kon", "description" => "KDE KOffice Office Suite - Kontour"),
		array("mime_type" => "application/vnd.kde.kpresenter", "extension" => "kpr", "description" => "KDE KOffice Office Suite - Kpresenter"),
		array("mime_type" => "application/vnd.kde.kspread", "extension" => "ksp", "description" => "KDE KOffice Office Suite - Kspread"),
		array("mime_type" => "application/vnd.kde.kword", "extension" => "kwd", "description" => "KDE KOffice Office Suite - Kword"),
		array("mime_type" => "application/vnd.kenameaapp", "extension" => "htke", "description" => "Kenamea App"),
		array("mime_type" => "application/vnd.kidspiration", "extension" => "kia", "description" => "Kidspiration"),
		array("mime_type" => "application/vnd.kinar", "extension" => "kne", "description" => "Kinar Applications"),
		array("mime_type" => "application/vnd.kodak-descriptor", "extension" => "sse", "description" => "Kodak Storyshare"),
		array("mime_type" => "application/vnd.las.las+xml", "extension" => "lasxml", "description" => "Laser App Enterprise"),
		array("mime_type" => "application/x-latex", "extension" => "latex", "description" => "LaTeX"),
		array("mime_type" => "application/vnd.llamagraphics.life-balance.desktop", "extension" => "lbd", "description" => "Life Balance - Desktop Edition"),
		array("mime_type" => "application/vnd.llamagraphics.life-balance.exchange+xml", "extension" => "lbe", "description" => "Life Balance - Exchange Format"),
		array("mime_type" => "application/vnd.jam", "extension" => "jam", "description" => "Lightspeed Audio Lab"),
		array("mime_type" => "application/vnd.lotus-1-2-3", "extension" => "123", "description" => "Lotus 1-2-3"),
		array("mime_type" => "application/vnd.lotus-approach", "extension" => "apr", "description" => "Lotus Approach"),
		array("mime_type" => "application/vnd.lotus-freelance", "extension" => "pre", "description" => "Lotus Freelance"),
		array("mime_type" => "application/vnd.lotus-notes", "extension" => "nsf", "description" => "Lotus Notes"),
		array("mime_type" => "application/vnd.lotus-organizer", "extension" => "org", "description" => "Lotus Organizer"),
		array("mime_type" => "application/vnd.lotus-screencam", "extension" => "scm", "description" => "Lotus Screencam"),
		array("mime_type" => "application/vnd.lotus-wordpro", "extension" => "lwp", "description" => "Lotus Wordpro"),
		array("mime_type" => "audio/vnd.lucent.voice", "extension" => "lvp", "description" => "Lucent Voice"),
		array("mime_type" => "audio/x-mpegurl", "extension" => "m3u", "description" => "M3U (Multimedia Playlist)"),
		array("mime_type" => "video/x-m4v", "extension" => "m4v", "description" => "M4v"),
		array("mime_type" => "application/mac-binhex40", "extension" => "hqx", "description" => "Macintosh BinHex 4.0"),
		array("mime_type" => "application/vnd.macports.portpkg", "extension" => "portpkg", "description" => "MacPorts Port System"),
		array("mime_type" => "application/vnd.osgeo.mapguide.package", "extension" => "mgp", "description" => "MapGuide DBXML"),
		array("mime_type" => "application/marc", "extension" => "mrc", "description" => "MARC Formats"),
		array("mime_type" => "application/marcxml+xml", "extension" => "mrcx", "description" => "MARC21 XML Schema"),
		array("mime_type" => "application/mxf", "extension" => "mxf", "description" => "Material Exchange Format"),
		array("mime_type" => "application/vnd.wolfram.player", "extension" => "nbp", "description" => "Mathematica Notebook Player"),
		array("mime_type" => "application/mathematica", "extension" => "ma", "description" => "Mathematica Notebooks"),
		array("mime_type" => "application/mathml+xml", "extension" => "mathml", "description" => "Mathematical Markup Language"),
		array("mime_type" => "application/mbox", "extension" => "mbox", "description" => "Mbox database files"),
		array("mime_type" => "application/vnd.medcalcdata", "extension" => "mc1", "description" => "MedCalc"),
		array("mime_type" => "application/mediaservercontrol+xml", "extension" => "mscml", "description" => "Media Server Control Markup Language"),
		array("mime_type" => "application/vnd.mediastation.cdkey", "extension" => "cdkey", "description" => "MediaRemote"),
		array("mime_type" => "application/vnd.mfer", "extension" => "mwf", "description" => "Medical Waveform Encoding Format"),
		array("mime_type" => "application/vnd.mfmp", "extension" => "mfm", "description" => "Melody Format for Mobile Platform"),
		array("mime_type" => "model/mesh", "extension" => "msh", "description" => "Mesh Data Type"),
		array("mime_type" => "application/mads+xml", "extension" => "mads", "description" => "Metadata Authority Description Schema"),
		array("mime_type" => "application/mets+xml", "extension" => "mets", "description" => "Metadata Encoding and Transmission Standard"),
		array("mime_type" => "application/mods+xml", "extension" => "mods", "description" => "Metadata Object Description Schema"),
		array("mime_type" => "application/metalink4+xml", "extension" => "meta4", "description" => "Metalink"),
		array("mime_type" => "application/vnd.ms-powerpoint.template.macroenabled.12", "extension" => "potm", "description" => "Micosoft PowerPoint - Macro-Enabled Template File"),
		array("mime_type" => "application/vnd.ms-word.document.macroenabled.12", "extension" => "docm", "description" => "Micosoft Word - Macro-Enabled Document"),
		array("mime_type" => "application/vnd.ms-word.template.macroenabled.12", "extension" => "dotm", "description" => "Micosoft Word - Macro-Enabled Template"),
		array("mime_type" => "application/vnd.mcd", "extension" => "mcd", "description" => "Micro CADAM Helix D&D"),
		array("mime_type" => "application/vnd.micrografx.flo", "extension" => "flo", "description" => "Micrografx"),
		array("mime_type" => "application/vnd.micrografx.igx", "extension" => "igx", "description" => "Micrografx iGrafx Professional"),
		array("mime_type" => "application/vnd.eszigno3+xml", "extension" => "es3", "description" => "MICROSEC e-Szign¢"),
		array("mime_type" => "application/x-msaccess", "extension" => "mdb", "description" => "Microsoft Access"),
		array("mime_type" => "video/x-ms-asf", "extension" => "asf", "description" => "Microsoft Advanced Systems Format (ASF)"),
		array("mime_type" => "application/x-msdownload", "extension" => "exe", "description" => "Microsoft Application"),
		array("mime_type" => "application/vnd.ms-artgalry", "extension" => "cil", "description" => "Microsoft Artgalry"),
		array("mime_type" => "application/vnd.ms-cab-compressed", "extension" => "cab", "description" => "Microsoft Cabinet File"),
		array("mime_type" => "application/vnd.ms-ims", "extension" => "ims", "description" => "Microsoft Class Server"),
		array("mime_type" => "application/x-ms-application", "extension" => "application", "description" => "Microsoft ClickOnce"),
		array("mime_type" => "application/x-msclip", "extension" => "clp", "description" => "Microsoft Clipboard Clip"),
		array("mime_type" => "image/vnd.ms-modi", "extension" => "mdi", "description" => "Microsoft Document Imaging Format"),
		array("mime_type" => "application/vnd.ms-fontobject", "extension" => "eot", "description" => "Microsoft Embedded OpenType"),
		array("mime_type" => "application/vnd.ms-excel", "extension" => "xls", "description" => "Microsoft Excel"),
		array("mime_type" => "application/vnd.ms-excel.addin.macroenabled.12", "extension" => "xlam", "description" => "Microsoft Excel - Add-In File"),
		array("mime_type" => "application/vnd.ms-excel.sheet.binary.macroenabled.12", "extension" => "xlsb", "description" => "Microsoft Excel - Binary Workbook"),
		array("mime_type" => "application/vnd.ms-excel.template.macroenabled.12", "extension" => "xltm", "description" => "Microsoft Excel - Macro-Enabled Template File"),
		array("mime_type" => "application/vnd.ms-excel.sheet.macroenabled.12", "extension" => "xlsm", "description" => "Microsoft Excel - Macro-Enabled Workbook"),
		array("mime_type" => "application/vnd.ms-htmlhelp", "extension" => "chm", "description" => "Microsoft Html Help File"),
		array("mime_type" => "application/x-mscardfile", "extension" => "crd", "description" => "Microsoft Information Card"),
		array("mime_type" => "application/vnd.ms-lrm", "extension" => "lrm", "description" => "Microsoft Learning Resource Module"),
		array("mime_type" => "application/x-msmediaview", "extension" => "mvb", "description" => "Microsoft MediaView"),
		array("mime_type" => "application/x-msmoney", "extension" => "mny", "description" => "Microsoft Money"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.presentationml.presentation", "extension" => "pptx", "description" => "Microsoft Office - OOXML - Presentation"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.presentationml.slide", "extension" => "sldx", "description" => "Microsoft Office - OOXML - Presentation (Slide)"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.presentationml.slideshow", "extension" => "ppsx", "description" => "Microsoft Office - OOXML - Presentation (Slideshow)"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.presentationml.template", "extension" => "potx", "description" => "Microsoft Office - OOXML - Presentation Template"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "extension" => "xlsx", "description" => "Microsoft Office - OOXML - Spreadsheet"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.template", "extension" => "xltx", "description" => "Microsoft Office - OOXML - Spreadsheet Teplate"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "extension" => "docx", "description" => "Microsoft Office - OOXML - Word Document"),
		array("mime_type" => "application/vnd.openxmlformats-officedocument.wordprocessingml.template", "extension" => "dotx", "description" => "Microsoft Office - OOXML - Word Document Template"),
		array("mime_type" => "application/x-msbinder", "extension" => "obd", "description" => "Microsoft Office Binder"),
		array("mime_type" => "application/vnd.ms-officetheme", "extension" => "thmx", "description" => "Microsoft Office System Release Theme"),
		array("mime_type" => "application/onenote", "extension" => "onetoc", "description" => "Microsoft OneNote"),
		array("mime_type" => "audio/vnd.ms-playready.media.pya", "extension" => "pya", "description" => "Microsoft PlayReady Ecosystem"),
		array("mime_type" => "video/vnd.ms-playready.media.pyv", "extension" => "pyv", "description" => "Microsoft PlayReady Ecosystem Video"),
		array("mime_type" => "application/vnd.ms-powerpoint", "extension" => "ppt", "description" => "Microsoft PowerPoint"),
		array("mime_type" => "application/vnd.ms-powerpoint.addin.macroenabled.12", "extension" => "ppam", "description" => "Microsoft PowerPoint - Add-in file"),
		array("mime_type" => "application/vnd.ms-powerpoint.slide.macroenabled.12", "extension" => "sldm", "description" => "Microsoft PowerPoint - Macro-Enabled Open XML Slide"),
		array("mime_type" => "application/vnd.ms-powerpoint.presentation.macroenabled.12", "extension" => "pptm", "description" => "Microsoft PowerPoint - Macro-Enabled Presentation File"),
		array("mime_type" => "application/vnd.ms-powerpoint.slideshow.macroenabled.12", "extension" => "ppsm", "description" => "Microsoft PowerPoint - Macro-Enabled Slide Show File"),
		array("mime_type" => "application/vnd.ms-project", "extension" => "mpp", "description" => "Microsoft Project"),
		array("mime_type" => "application/x-mspublisher", "extension" => "pub", "description" => "Microsoft Publisher"),
		array("mime_type" => "application/x-msschedule", "extension" => "scd", "description" => "Microsoft Schedule+"),
		array("mime_type" => "application/x-silverlight-app", "extension" => "xap", "description" => "Microsoft Silverlight"),
		array("mime_type" => "application/vnd.ms-pki.stl", "extension" => "stl", "description" => "Microsoft Trust UI Provider - Certificate Trust Link"),
		array("mime_type" => "application/vnd.ms-pki.seccat", "extension" => "cat", "description" => "Microsoft Trust UI Provider - Security Catalog"),
		array("mime_type" => "application/vnd.visio", "extension" => "vsd", "description" => "Microsoft Visio"),
		array("mime_type" => "video/x-ms-wm", "extension" => "wm", "description" => "Microsoft Windows Media"),
		array("mime_type" => "audio/x-ms-wma", "extension" => "wma", "description" => "Microsoft Windows Media Audio"),
		array("mime_type" => "audio/x-ms-wax", "extension" => "wax", "description" => "Microsoft Windows Media Audio Redirector"),
		array("mime_type" => "video/x-ms-wmx", "extension" => "wmx", "description" => "Microsoft Windows Media Audio/Video Playlist"),
		array("mime_type" => "application/x-ms-wmd", "extension" => "wmd", "description" => "Microsoft Windows Media Player Download Package"),
		array("mime_type" => "application/vnd.ms-wpl", "extension" => "wpl", "description" => "Microsoft Windows Media Player Playlist"),
		array("mime_type" => "application/x-ms-wmz", "extension" => "wmz", "description" => "Microsoft Windows Media Player Skin Package"),
		array("mime_type" => "video/x-ms-wmv", "extension" => "wmv", "description" => "Microsoft Windows Media Video"),
		array("mime_type" => "video/x-ms-wvx", "extension" => "wvx", "description" => "Microsoft Windows Media Video Playlist"),
		array("mime_type" => "application/x-msmetafile", "extension" => "wmf", "description" => "Microsoft Windows Metafile"),
		array("mime_type" => "application/x-msterminal", "extension" => "trm", "description" => "Microsoft Windows Terminal Services"),
		array("mime_type" => "application/msword", "extension" => "doc", "description" => "Microsoft Word"),
		array("mime_type" => "application/x-mswrite", "extension" => "wri", "description" => "Microsoft Wordpad"),
		array("mime_type" => "application/vnd.ms-works", "extension" => "wps", "description" => "Microsoft Works"),
		array("mime_type" => "application/x-ms-xbap", "extension" => "xbap", "description" => "Microsoft XAML Browser Application"),
		array("mime_type" => "application/vnd.ms-xpsdocument", "extension" => "xps", "description" => "Microsoft XML Paper Specification"),
		array("mime_type" => "audio/midi", "extension" => "mid", "description" => "MIDI - Musical Instrument Digital Interface"),
		array("mime_type" => "application/vnd.ibm.minipay", "extension" => "mpy", "description" => "MiniPay"),
		array("mime_type" => "application/vnd.ibm.modcap", "extension" => "afp", "description" => "MO:DCA-P"),
		array("mime_type" => "application/vnd.jcp.javame.midlet-rms", "extension" => "rms", "description" => "Mobile Information Device Profile"),
		array("mime_type" => "application/vnd.tmobile-livetv", "extension" => "tmo", "description" => "MobileTV"),
		array("mime_type" => "application/x-mobipocket-ebook", "extension" => "prc", "description" => "Mobipocket"),
		array("mime_type" => "application/vnd.mobius.mbk", "extension" => "mbk", "description" => "Mobius Management Systems - Basket file"),
		array("mime_type" => "application/vnd.mobius.dis", "extension" => "dis", "description" => "Mobius Management Systems - Distribution Database"),
		array("mime_type" => "application/vnd.mobius.plc", "extension" => "plc", "description" => "Mobius Management Systems - Policy Definition Language File"),
		array("mime_type" => "application/vnd.mobius.mqy", "extension" => "mqy", "description" => "Mobius Management Systems - Query File"),
		array("mime_type" => "application/vnd.mobius.msl", "extension" => "msl", "description" => "Mobius Management Systems - Script Language"),
		array("mime_type" => "application/vnd.mobius.txf", "extension" => "txf", "description" => "Mobius Management Systems - Topic Index File"),
		array("mime_type" => "application/vnd.mobius.daf", "extension" => "daf", "description" => "Mobius Management Systems - UniversalArchive"),
		array("mime_type" => "text/vnd.fly", "extension" => "fly", "description" => "mod_fly / fly.cgi"),
		array("mime_type" => "application/vnd.mophun.certificate", "extension" => "mpc", "description" => "Mophun Certificate"),
		array("mime_type" => "application/vnd.mophun.application", "extension" => "mpn", "description" => "Mophun VM"),
		array("mime_type" => "video/mj2", "extension" => "mj2", "description" => "Motion JPEG 2000"),
		array("mime_type" => "audio/mpeg", "extension" => "mpga", "description" => "MPEG Audio"),
		array("mime_type" => "video/vnd.mpegurl", "extension" => "mxu", "description" => "MPEG Url"),
		array("mime_type" => "video/mpeg", "extension" => "mpeg", "description" => "MPEG Video"),
		array("mime_type" => "application/mp21", "extension" => "m21", "description" => "MPEG-21"),
		array("mime_type" => "audio/mp4", "extension" => "mp4a", "description" => "MPEG-4 Audio"),
		array("mime_type" => "video/mp4", "extension" => "mp4", "description" => "MPEG-4 Video"),
		array("mime_type" => "application/mp4", "extension" => "mp4", "description" => "MPEG4"),
		array("mime_type" => "application/vnd.apple.mpegurl", "extension" => "m3u8", "description" => "Multimedia Playlist Unicode"),
		array("mime_type" => "application/vnd.musician", "extension" => "mus", "description" => "MUsical Score Interpreted Code Invented for the ASCII designation of Notation"),
		array("mime_type" => "application/vnd.muvee.style", "extension" => "msty", "description" => "Muvee Automatic Video Editing"),
		array("mime_type" => "application/xv+xml", "extension" => "mxml", "description" => "MXML"),
		array("mime_type" => "application/vnd.nokia.n-gage.data", "extension" => "ngdat", "description" => "N-Gage Game Data"),
		array("mime_type" => "application/vnd.nokia.n-gage.symbian.install", "extension" => "n-gage", "description" => "N-Gage Game Installer"),
		array("mime_type" => "application/x-dtbncx+xml", "extension" => "ncx", "description" => "Navigation Control file for XML (for ePub)"),
		array("mime_type" => "application/x-netcdf", "extension" => "nc", "description" => "Network Common Data Form (NetCDF)"),
		array("mime_type" => "application/vnd.neurolanguage.nlu", "extension" => "nlu", "description" => "neuroLanguage"),
		array("mime_type" => "application/vnd.dna", "extension" => "dna", "description" => "New Moon Liftoff/DNA"),
		array("mime_type" => "application/vnd.noblenet-directory", "extension" => "nnd", "description" => "NobleNet Directory"),
		array("mime_type" => "application/vnd.noblenet-sealer", "extension" => "nns", "description" => "NobleNet Sealer"),
		array("mime_type" => "application/vnd.noblenet-web", "extension" => "nnw", "description" => "NobleNet Web"),
		array("mime_type" => "application/vnd.nokia.radio-preset", "extension" => "rpst", "description" => "Nokia Radio Application - Preset"),
		array("mime_type" => "application/vnd.nokia.radio-presets", "extension" => "rpss", "description" => "Nokia Radio Application - Preset"),
		array("mime_type" => "text/n3", "extension" => "n3", "description" => "Notation3"),
		array("mime_type" => "application/vnd.novadigm.edm", "extension" => "edm", "description" => "Novadigm's RADIA and EDM products"),
		array("mime_type" => "application/vnd.novadigm.edx", "extension" => "edx", "description" => "Novadigm's RADIA and EDM products"),
		array("mime_type" => "application/vnd.novadigm.ext", "extension" => "ext", "description" => "Novadigm's RADIA and EDM products"),
		array("mime_type" => "application/vnd.flographit", "extension" => "gph", "description" => "NpGraphIt"),
		array("mime_type" => "audio/vnd.nuera.ecelp4800", "extension" => "ecelp4800", "description" => "Nuera ECELP 4800"),
		array("mime_type" => "audio/vnd.nuera.ecelp7470", "extension" => "ecelp7470", "description" => "Nuera ECELP 7470"),
		array("mime_type" => "audio/vnd.nuera.ecelp9600", "extension" => "ecelp9600", "description" => "Nuera ECELP 9600"),
		array("mime_type" => "application/oda", "extension" => "oda", "description" => "Office Document Architecture"),
		array("mime_type" => "application/ogg", "extension" => "ogx", "description" => "Ogg"),
		array("mime_type" => "audio/ogg", "extension" => "oga", "description" => "Ogg Audio"),
		array("mime_type" => "video/ogg", "extension" => "ogv", "description" => "Ogg Video"),
		array("mime_type" => "application/vnd.oma.dd2+xml", "extension" => "dd2", "description" => "OMA Download Agents"),
		array("mime_type" => "application/vnd.oasis.opendocument.text-web", "extension" => "oth", "description" => "Open Document Text Web"),
		array("mime_type" => "application/oebps-package+xml", "extension" => "opf", "description" => "Open eBook Publication Structure"),
		array("mime_type" => "application/vnd.intu.qbo", "extension" => "qbo", "description" => "Open Financial Exchange"),
		array("mime_type" => "application/vnd.openofficeorg.extension", "extension" => "oxt", "description" => "Open Office Extension"),
		array("mime_type" => "application/vnd.yamaha.openscoreformat", "extension" => "osf", "description" => "Open Score Format"),
		array("mime_type" => "audio/webm", "extension" => "weba", "description" => "Open Web Media Project - Audio"),
		array("mime_type" => "video/webm", "extension" => "webm", "description" => "Open Web Media Project - Video"),
		array("mime_type" => "application/vnd.oasis.opendocument.chart", "extension" => "odc", "description" => "OpenDocument Chart"),
		array("mime_type" => "application/vnd.oasis.opendocument.chart-template", "extension" => "otc", "description" => "OpenDocument Chart Template"),
		array("mime_type" => "application/vnd.oasis.opendocument.database", "extension" => "odb", "description" => "OpenDocument Database"),
		array("mime_type" => "application/vnd.oasis.opendocument.formula", "extension" => "odf", "description" => "OpenDocument Formula"),
		array("mime_type" => "application/vnd.oasis.opendocument.formula-template", "extension" => "odft", "description" => "OpenDocument Formula Template"),
		array("mime_type" => "application/vnd.oasis.opendocument.graphics", "extension" => "odg", "description" => "OpenDocument Graphics"),
		array("mime_type" => "application/vnd.oasis.opendocument.graphics-template", "extension" => "otg", "description" => "OpenDocument Graphics Template"),
		array("mime_type" => "application/vnd.oasis.opendocument.image", "extension" => "odi", "description" => "OpenDocument Image"),
		array("mime_type" => "application/vnd.oasis.opendocument.image-template", "extension" => "oti", "description" => "OpenDocument Image Template"),
		array("mime_type" => "application/vnd.oasis.opendocument.presentation", "extension" => "odp", "description" => "OpenDocument Presentation"),
		array("mime_type" => "application/vnd.oasis.opendocument.presentation-template", "extension" => "otp", "description" => "OpenDocument Presentation Template"),
		array("mime_type" => "application/vnd.oasis.opendocument.spreadsheet", "extension" => "ods", "description" => "OpenDocument Spreadsheet"),
		array("mime_type" => "application/vnd.oasis.opendocument.spreadsheet-template", "extension" => "ots", "description" => "OpenDocument Spreadsheet Template"),
		array("mime_type" => "application/vnd.oasis.opendocument.text", "extension" => "odt", "description" => "OpenDocument Text"),
		array("mime_type" => "application/vnd.oasis.opendocument.text-master", "extension" => "odm", "description" => "OpenDocument Text Master"),
		array("mime_type" => "application/vnd.oasis.opendocument.text-template", "extension" => "ott", "description" => "OpenDocument Text Template"),
		array("mime_type" => "image/ktx", "extension" => "ktx", "description" => "OpenGL Textures (KTX)"),
		array("mime_type" => "application/vnd.sun.xml.calc", "extension" => "sxc", "description" => "OpenOffice - Calc (Spreadsheet)"),
		array("mime_type" => "application/vnd.sun.xml.calc.template", "extension" => "stc", "description" => "OpenOffice - Calc Template (Spreadsheet)"),
		array("mime_type" => "application/vnd.sun.xml.draw", "extension" => "sxd", "description" => "OpenOffice - Draw (Graphics)"),
		array("mime_type" => "application/vnd.sun.xml.draw.template", "extension" => "std", "description" => "OpenOffice - Draw Template (Graphics)"),
		array("mime_type" => "application/vnd.sun.xml.impress", "extension" => "sxi", "description" => "OpenOffice - Impress (Presentation)"),
		array("mime_type" => "application/vnd.sun.xml.impress.template", "extension" => "sti", "description" => "OpenOffice - Impress Template (Presentation)"),
		array("mime_type" => "application/vnd.sun.xml.math", "extension" => "sxm", "description" => "OpenOffice - Math (Formula)"),
		array("mime_type" => "application/vnd.sun.xml.writer", "extension" => "sxw", "description" => "OpenOffice - Writer (Text - HTML)"),
		array("mime_type" => "application/vnd.sun.xml.writer.global", "extension" => "sxg", "description" => "OpenOffice - Writer (Text - HTML)"),
		array("mime_type" => "application/vnd.sun.xml.writer.template", "extension" => "stw", "description" => "OpenOffice - Writer Template (Text - HTML)"),
		array("mime_type" => "application/x-font-otf", "extension" => "otf", "description" => "OpenType Font File"),
		array("mime_type" => "application/vnd.yamaha.openscoreformat.osfpvg+xml", "extension" => "osfpvg", "description" => "OSFPVG"),
		array("mime_type" => "application/vnd.osgi.dp", "extension" => "dp", "description" => "OSGi Deployment Package"),
		array("mime_type" => "application/vnd.palm", "extension" => "pdb", "description" => "PalmOS Data"),
		array("mime_type" => "text/x-pascal", "extension" => "p", "description" => "Pascal Source File"),
		array("mime_type" => "application/vnd.pawaafile", "extension" => "paw", "description" => "PawaaFILE"),
		array("mime_type" => "application/vnd.hp-pclxl", "extension" => "pclxl", "description" => "PCL 6 Enhanced (Formely PCL XL)"),
		array("mime_type" => "application/vnd.picsel", "extension" => "efif", "description" => "Pcsel eFIF File"),
		array("mime_type" => "image/x-pcx", "extension" => "pcx", "description" => "PCX Image"),
		array("mime_type" => "image/vnd.adobe.photoshop", "extension" => "psd", "description" => "Photoshop Document"),
		array("mime_type" => "application/pics-rules", "extension" => "prf", "description" => "PICSRules"),
		array("mime_type" => "image/x-pict", "extension" => "pic", "description" => "PICT Image"),
		array("mime_type" => "application/x-chat", "extension" => "chat", "description" => "pIRCh"),
		array("mime_type" => "application/pkcs10", "extension" => "p10", "description" => "PKCS #10 - Certification Request Standard"),
		array("mime_type" => "application/x-pkcs12", "extension" => "p12", "description" => "PKCS #12 - Personal Information Exchange Syntax Standard"),
		array("mime_type" => "application/pkcs7-mime", "extension" => "p7m", "description" => "PKCS #7 - Cryptographic Message Syntax Standard"),
		array("mime_type" => "application/pkcs7-signature", "extension" => "p7s", "description" => "PKCS #7 - Cryptographic Message Syntax Standard"),
		array("mime_type" => "application/x-pkcs7-certreqresp", "extension" => "p7r", "description" => "PKCS #7 - Cryptographic Message Syntax Standard (Certificate Request Response)"),
		array("mime_type" => "application/x-pkcs7-certificates", "extension" => "p7b", "description" => "PKCS #7 - Cryptographic Message Syntax Standard (Certificates)"),
		array("mime_type" => "application/pkcs8", "extension" => "p8", "description" => "PKCS #8 - Private-Key Information Syntax Standard"),
		array("mime_type" => "application/vnd.pocketlearn", "extension" => "plf", "description" => "PocketLearn Viewers"),
		array("mime_type" => "image/x-portable-anymap", "extension" => "pnm", "description" => "Portable Anymap Image"),
		array("mime_type" => "image/x-portable-bitmap", "extension" => "pbm", "description" => "Portable Bitmap Format"),
		array("mime_type" => "application/x-font-pcf", "extension" => "pcf", "description" => "Portable Compiled Format"),
		array("mime_type" => "application/font-tdpfr", "extension" => "pfr", "description" => "Portable Font Resource"),
		array("mime_type" => "application/x-chess-pgn", "extension" => "pgn", "description" => "Portable Game Notation (Chess Games)"),
		array("mime_type" => "image/x-portable-graymap", "extension" => "pgm", "description" => "Portable Graymap Format"),
		array("mime_type" => "image/png", "extension" => "png", "description" => "Portable Network Graphics (PNG)"),
		array("mime_type" => "image/x-portable-pixmap", "extension" => "ppm", "description" => "Portable Pixmap Format"),
		array("mime_type" => "application/pskc+xml", "extension" => "pskcxml", "description" => "Portable Symmetric Key Container"),
		array("mime_type" => "application/vnd.ctc-posml", "extension" => "pml", "description" => "PosML"),
		array("mime_type" => "application/postscript", "extension" => "ai", "description" => "PostScript"),
		array("mime_type" => "application/x-font-type1", "extension" => "pfa", "description" => "PostScript Fonts"),
		array("mime_type" => "application/vnd.powerbuilder6", "extension" => "pbd", "description" => "PowerBuilder"),
		array("mime_type" => "application/pgp-encrypted", "extension" => "", "description" => "Pretty Good Privacy"),
		array("mime_type" => "application/pgp-signature", "extension" => "pgp", "description" => "Pretty Good Privacy - Signature"),
		array("mime_type" => "application/vnd.previewsystems.box", "extension" => "box", "description" => "Preview Systems ZipLock/VBox"),
		array("mime_type" => "application/vnd.pvi.ptid1", "extension" => "ptid", "description" => "Princeton Video Image"),
		array("mime_type" => "application/pls+xml", "extension" => "pls", "description" => "Pronunciation Lexicon Specification"),
		array("mime_type" => "application/vnd.pg.format", "extension" => "str", "description" => "Proprietary P&G Standard Reporting System"),
		array("mime_type" => "application/vnd.pg.osasli", "extension" => "ei6", "description" => "Proprietary P&G Standard Reporting System"),
		array("mime_type" => "text/prs.lines.tag", "extension" => "dsc", "description" => "PRS Lines Tag"),
		array("mime_type" => "application/x-font-linux-psf", "extension" => "psf", "description" => "PSF Fonts"),
		array("mime_type" => "application/vnd.publishare-delta-tree", "extension" => "qps", "description" => "PubliShare Objects"),
		array("mime_type" => "application/vnd.pmi.widget", "extension" => "wg", "description" => "Qualcomm's Plaza Mobile Internet"),
		array("mime_type" => "application/vnd.quark.quarkxpress", "extension" => "qxd", "description" => "QuarkXpress"),
		array("mime_type" => "application/vnd.epson.esf", "extension" => "esf", "description" => "QUASS Stream Player"),
		array("mime_type" => "application/vnd.epson.msf", "extension" => "msf", "description" => "QUASS Stream Player"),
		array("mime_type" => "application/vnd.epson.ssf", "extension" => "ssf", "description" => "QUASS Stream Player"),
		array("mime_type" => "application/vnd.epson.quickanime", "extension" => "qam", "description" => "QuickAnime Player"),
		array("mime_type" => "application/vnd.intu.qfx", "extension" => "qfx", "description" => "Quicken"),
		array("mime_type" => "video/quicktime", "extension" => "qt", "description" => "Quicktime Video"),
		array("mime_type" => "application/x-rar-compressed", "extension" => "rar", "description" => "RAR Archive"),
		array("mime_type" => "audio/x-pn-realaudio", "extension" => "ram", "description" => "Real Audio Sound"),
		array("mime_type" => "audio/x-pn-realaudio-plugin", "extension" => "rmp", "description" => "Real Audio Sound"),
		array("mime_type" => "application/rsd+xml", "extension" => "rsd", "description" => "Really Simple Discovery"),
		array("mime_type" => "application/vnd.rn-realmedia", "extension" => "rm", "description" => "RealMedia"),
		array("mime_type" => "application/vnd.realvnc.bed", "extension" => "bed", "description" => "RealVNC"),
		array("mime_type" => "application/vnd.recordare.musicxml", "extension" => "mxl", "description" => "Recordare Applications"),
		array("mime_type" => "application/vnd.recordare.musicxml+xml", "extension" => "musicxml", "description" => "Recordare Applications"),
		array("mime_type" => "application/relax-ng-compact-syntax", "extension" => "rnc", "description" => "Relax NG Compact Syntax"),
		array("mime_type" => "application/vnd.data-vision.rdz", "extension" => "rdz", "description" => "RemoteDocs R-Viewer"),
		array("mime_type" => "application/rdf+xml", "extension" => "rdf", "description" => "Resource Description Framework"),
		array("mime_type" => "application/vnd.cloanto.rp9", "extension" => "rp9", "description" => "RetroPlatform Player"),
		array("mime_type" => "application/vnd.jisp", "extension" => "jisp", "description" => "RhymBox"),
		array("mime_type" => "application/rtf", "extension" => "rtf", "description" => "Rich Text Format"),
		array("mime_type" => "text/richtext", "extension" => "rtx", "description" => "Rich Text Format (RTF)"),
		array("mime_type" => "application/vnd.route66.link66+xml", "extension" => "link66", "description" => "ROUTE 66 Location Based Services"),
		array("mime_type" => "application/rss+xml", "extension" => "rss, xml", "description" => "RSS - Really Simple Syndication"),
		array("mime_type" => "application/shf+xml", "extension" => "shf", "description" => "S Hexdump Format"),
		array("mime_type" => "application/vnd.sailingtracker.track", "extension" => "st", "description" => "SailingTracker"),
		array("mime_type" => "image/svg+xml", "extension" => "svg", "description" => "Scalable Vector Graphics (SVG)"),
		array("mime_type" => "image/svg", "extension" => "svg", "description" => "Scalable Vector Graphics (SVG)"),
		array("mime_type" => "application/vnd.sus-calendar", "extension" => "sus", "description" => "ScheduleUs"),
		array("mime_type" => "application/sru+xml", "extension" => "sru", "description" => "Search/Retrieve via URL Response Format"),
		array("mime_type" => "application/set-payment-initiation", "extension" => "setpay", "description" => "Secure Electronic Transaction - Payment"),
		array("mime_type" => "application/set-registration-initiation", "extension" => "setreg", "description" => "Secure Electronic Transaction - Registration"),
		array("mime_type" => "application/vnd.sema", "extension" => "sema", "description" => "Secured eMail"),
		array("mime_type" => "application/vnd.semd", "extension" => "semd", "description" => "Secured eMail"),
		array("mime_type" => "application/vnd.semf", "extension" => "semf", "description" => "Secured eMail"),
		array("mime_type" => "application/vnd.seemail", "extension" => "see", "description" => "SeeMail"),
		array("mime_type" => "application/x-font-snf", "extension" => "snf", "description" => "Server Normal Format"),
		array("mime_type" => "application/scvp-vp-request", "extension" => "spq", "description" => "Server-Based Certificate Validation Protocol - Validation Policies - Request"),
		array("mime_type" => "application/scvp-vp-response", "extension" => "spp", "description" => "Server-Based Certificate Validation Protocol - Validation Policies - Response"),
		array("mime_type" => "application/scvp-cv-request", "extension" => "scq", "description" => "Server-Based Certificate Validation Protocol - Validation Request"),
		array("mime_type" => "application/scvp-cv-response", "extension" => "scs", "description" => "Server-Based Certificate Validation Protocol - Validation Response"),
		array("mime_type" => "application/sdp", "extension" => "sdp", "description" => "Session Description Protocol"),
		array("mime_type" => "text/x-setext", "extension" => "etx", "description" => "Setext"),
		array("mime_type" => "video/x-sgi-movie", "extension" => "movie", "description" => "SGI Movie"),
		array("mime_type" => "application/vnd.shana.informed.formdata", "extension" => "ifm", "description" => "Shana Informed Filler"),
		array("mime_type" => "application/vnd.shana.informed.formtemplate", "extension" => "itp", "description" => "Shana Informed Filler"),
		array("mime_type" => "application/vnd.shana.informed.interchange", "extension" => "iif", "description" => "Shana Informed Filler"),
		array("mime_type" => "application/vnd.shana.informed.package", "extension" => "ipk", "description" => "Shana Informed Filler"),
		array("mime_type" => "application/thraud+xml", "extension" => "tfi", "description" => "Sharing Transaction Fraud Data"),
		array("mime_type" => "application/x-shar", "extension" => "shar", "description" => "Shell Archive"),
		array("mime_type" => "image/x-rgb", "extension" => "rgb", "description" => "Silicon Graphics RGB Bitmap"),
		array("mime_type" => "application/vnd.epson.salt", "extension" => "slt", "description" => "SimpleAnimeLite Player"),
		array("mime_type" => "application/vnd.accpac.simply.aso", "extension" => "aso", "description" => "Simply Accounting"),
		array("mime_type" => "application/vnd.accpac.simply.imp", "extension" => "imp", "description" => "Simply Accounting - Data Import"),
		array("mime_type" => "application/vnd.simtech-mindmapper", "extension" => "twd", "description" => "SimTech MindMapper"),
		array("mime_type" => "application/vnd.commonspace", "extension" => "csp", "description" => "Sixth Floor Media - CommonSpace"),
		array("mime_type" => "application/vnd.yamaha.smaf-audio", "extension" => "saf", "description" => "SMAF Audio"),
		array("mime_type" => "application/vnd.smaf", "extension" => "mmf", "description" => "SMAF File"),
		array("mime_type" => "application/vnd.yamaha.smaf-phrase", "extension" => "spf", "description" => "SMAF Phrase"),
		array("mime_type" => "application/vnd.smart.teacher", "extension" => "teacher", "description" => "SMART Technologies Apps"),
		array("mime_type" => "application/vnd.svd", "extension" => "svd", "description" => "SourceView Document"),
		array("mime_type" => "application/sparql-query", "extension" => "rq", "description" => "SPARQL - Query"),
		array("mime_type" => "application/sparql-results+xml", "extension" => "srx", "description" => "SPARQL - Results"),
		array("mime_type" => "application/srgs", "extension" => "gram", "description" => "Speech Recognition Grammar Specification"),
		array("mime_type" => "application/srgs+xml", "extension" => "grxml", "description" => "Speech Recognition Grammar Specification - XML"),
		array("mime_type" => "application/ssml+xml", "extension" => "ssml", "description" => "Speech Synthesis Markup Language"),
		array("mime_type" => "application/vnd.koan", "extension" => "skp", "description" => "SSEYO Koan Play File"),
		array("mime_type" => "text/sgml", "extension" => "sgml", "description" => "Standard Generalized Markup Language (SGML)"),
		array("mime_type" => "application/vnd.stardivision.calc", "extension" => "sdc", "description" => "StarOffice - Calc"),
		array("mime_type" => "application/vnd.stardivision.draw", "extension" => "sda", "description" => "StarOffice - Draw"),
		array("mime_type" => "application/vnd.stardivision.impress", "extension" => "sdd", "description" => "StarOffice - Impress"),
		array("mime_type" => "application/vnd.stardivision.math", "extension" => "smf", "description" => "StarOffice - Math"),
		array("mime_type" => "application/vnd.stardivision.writer", "extension" => "sdw", "description" => "StarOffice - Writer"),
		array("mime_type" => "application/vnd.stardivision.writer-global", "extension" => "sgl", "description" => "StarOffice - Writer (Global)"),
		array("mime_type" => "application/vnd.stepmania.stepchart", "extension" => "sm", "description" => "StepMania"),
		array("mime_type" => "application/x-stuffit", "extension" => "sit", "description" => "Stuffit Archive"),
		array("mime_type" => "application/x-stuffitx", "extension" => "sitx", "description" => "Stuffit Archive"),
		array("mime_type" => "application/vnd.solent.sdkm+xml", "extension" => "sdkm", "description" => "SudokuMagic"),
		array("mime_type" => "application/vnd.olpc-sugar", "extension" => "xo", "description" => "Sugar Linux Application Bundle"),
		array("mime_type" => "audio/basic", "extension" => "au", "description" => "Sun Audio - Au file format"),
		array("mime_type" => "application/vnd.wqd", "extension" => "wqd", "description" => "SundaHus WQ"),
		array("mime_type" => "application/vnd.symbian.install", "extension" => "sis", "description" => "Symbian Install Package"),
		array("mime_type" => "application/smil+xml", "extension" => "smi", "description" => "Synchronized Multimedia Integration Language"),
		array("mime_type" => "application/vnd.syncml+xml", "extension" => "xsm", "description" => "SyncML"),
		array("mime_type" => "application/vnd.syncml.dm+wbxml", "extension" => "bdm", "description" => "SyncML - Device Management"),
		array("mime_type" => "application/vnd.syncml.dm+xml", "extension" => "xdm", "description" => "SyncML - Device Management"),
		array("mime_type" => "application/x-sv4cpio", "extension" => "sv4cpio", "description" => "System V Release 4 CPIO Archive"),
		array("mime_type" => "application/x-sv4crc", "extension" => "sv4crc", "description" => "System V Release 4 CPIO Checksum Data"),
		array("mime_type" => "application/sbml+xml", "extension" => "sbml", "description" => "Systems Biology Markup Language"),
		array("mime_type" => "text/tab-separated-values", "extension" => "tsv", "description" => "Tab Seperated Values"),
		array("mime_type" => "image/tiff", "extension" => "tiff", "description" => "Tagged Image File Format"),
		array("mime_type" => "application/vnd.tao.intent-module-archive", "extension" => "tao", "description" => "Tao Intent"),
		array("mime_type" => "application/x-tar", "extension" => "tar", "description" => "Tar File (Tape Archive)"),
		array("mime_type" => "application/x-tcl", "extension" => "tcl", "description" => "Tcl Script"),
		array("mime_type" => "application/x-tex", "extension" => "tex", "description" => "TeX"),
		array("mime_type" => "application/x-tex-tfm", "extension" => "tfm", "description" => "TeX Font Metric"),
		array("mime_type" => "application/tei+xml", "extension" => "tei", "description" => "Text Encoding and Interchange"),
		array("mime_type" => "text/plain", "extension" => "txt", "description" => "Text File"),
		array("mime_type" => "application/vnd.spotfire.dxp", "extension" => "dxp", "description" => "TIBCO Spotfire"),
		array("mime_type" => "application/vnd.spotfire.sfs", "extension" => "sfs", "description" => "TIBCO Spotfire"),
		array("mime_type" => "application/timestamped-data", "extension" => "tsd", "description" => "Time Stamped Data Envelope"),
		array("mime_type" => "application/vnd.trid.tpt", "extension" => "tpt", "description" => "TRI Systems Config"),
		array("mime_type" => "application/vnd.triscape.mxs", "extension" => "mxs", "description" => "Triscape Map Explorer"),
		array("mime_type" => "text/troff", "extension" => "t", "description" => "troff"),
		array("mime_type" => "application/vnd.trueapp", "extension" => "tra", "description" => "True BASIC"),
		array("mime_type" => "application/x-font-ttf", "extension" => "ttf", "description" => "TrueType Font"),
		array("mime_type" => "text/turtle", "extension" => "ttl", "description" => "Turtle (Terse RDF Triple Language)"),
		array("mime_type" => "application/vnd.umajin", "extension" => "umj", "description" => "UMAJIN"),
		array("mime_type" => "application/vnd.uoml+xml", "extension" => "uoml", "description" => "Unique Object Markup Language"),
		array("mime_type" => "application/vnd.unity", "extension" => "unityweb", "description" => "Unity 3d"),
		array("mime_type" => "application/vnd.ufdl", "extension" => "ufd", "description" => "Universal Forms Description Language"),
		array("mime_type" => "text/uri-list", "extension" => "uri", "description" => "URI Resolution Services"),
		array("mime_type" => "application/vnd.uiq.theme", "extension" => "utz", "description" => "User Interface Quartz - Theme (Symbian)"),
		array("mime_type" => "application/x-ustar", "extension" => "ustar", "description" => "Ustar (Uniform Standard Tape Archive)"),
		array("mime_type" => "text/x-uuencode", "extension" => "uu", "description" => "UUEncode"),
		array("mime_type" => "text/x-vcalendar", "extension" => "vcs", "description" => "vCalendar"),
		array("mime_type" => "text/x-vcard", "extension" => "vcf", "description" => "vCard"),
		array("mime_type" => "application/x-cdlink", "extension" => "vcd", "description" => "Video CD"),
		array("mime_type" => "application/vnd.vsf", "extension" => "vsf", "description" => "Viewport+"),
		array("mime_type" => "model/vrml", "extension" => "wrl", "description" => "Virtual Reality Modeling Language"),
		array("mime_type" => "application/vnd.vcx", "extension" => "vcx", "description" => "VirtualCatalog"),
		array("mime_type" => "model/vnd.mts", "extension" => "mts", "description" => "Virtue MTS"),
		array("mime_type" => "model/vnd.vtu", "extension" => "vtu", "description" => "Virtue VTU"),
		array("mime_type" => "application/vnd.visionary", "extension" => "vis", "description" => "Visionary"),
		array("mime_type" => "video/vnd.vivo", "extension" => "viv", "description" => "Vivo"),
		array("mime_type" => "application/ccxml+xml", "extension" => "ccxml", "description" => "Voice Browser Call Control"),
		array("mime_type" => "application/voicexml+xml", "extension" => "vxml", "description" => "VoiceXML"),
		array("mime_type" => "application/x-wais-source", "extension" => "src", "description" => "WAIS Source"),
		array("mime_type" => "application/vnd.wap.wbxml", "extension" => "wbxml", "description" => "WAP Binary XML (WBXML)"),
		array("mime_type" => "image/vnd.wap.wbmp", "extension" => "wbmp", "description" => "WAP Bitamp (WBMP)"),
		array("mime_type" => "audio/x-wav", "extension" => "wav", "description" => "Waveform Audio File Format (WAV)"),
		array("mime_type" => "application/davmount+xml", "extension" => "davmount", "description" => "Web Distributed Authoring and Versioning"),
		array("mime_type" => "application/x-font-woff", "extension" => "woff", "description" => "Web Open Font Format"),
		array("mime_type" => "application/wspolicy+xml", "extension" => "wspolicy", "description" => "Web Services Policy"),
		array("mime_type" => "image/webp", "extension" => "webp", "description" => "WebP Image"),
		array("mime_type" => "application/vnd.webturbo", "extension" => "wtb", "description" => "WebTurbo"),
		array("mime_type" => "application/widget", "extension" => "wgt", "description" => "Widget Packaging and XML Configuration"),
		array("mime_type" => "application/winhlp", "extension" => "hlp", "description" => "WinHelp"),
		array("mime_type" => "text/vnd.wap.wml", "extension" => "wml", "description" => "Wireless Markup Language (WML)"),
		array("mime_type" => "text/vnd.wap.wmlscript", "extension" => "wmls", "description" => "Wireless Markup Language Script (WMLScript)"),
		array("mime_type" => "application/vnd.wap.wmlscriptc", "extension" => "wmlsc", "description" => "WMLScript"),
		array("mime_type" => "application/vnd.wordperfect", "extension" => "wpd", "description" => "Wordperfect"),
		array("mime_type" => "application/vnd.wt.stf", "extension" => "stf", "description" => "Worldtalk"),
		array("mime_type" => "application/wsdl+xml", "extension" => "wsdl", "description" => "WSDL - Web Services Description Language"),
		array("mime_type" => "image/x-xbitmap", "extension" => "xbm", "description" => "X BitMap"),
		array("mime_type" => "image/x-xpixmap", "extension" => "xpm", "description" => "X PixMap"),
		array("mime_type" => "image/x-xwindowdump", "extension" => "xwd", "description" => "X Window Dump"),
		array("mime_type" => "application/x-x509-ca-cert", "extension" => "der", "description" => "X.509 Certificate"),
		array("mime_type" => "application/x-xfig", "extension" => "fig", "description" => "Xfig"),
		array("mime_type" => "application/xhtml+xml", "extension" => "xhtml", "description" => "XHTML - The Extensible HyperText Markup Language"),
		array("mime_type" => "application/xml", "extension" => "xml", "description" => "XML - Extensible Markup Language"),
		array("mime_type" => "application/xcap-diff+xml", "extension" => "xdf", "description" => "XML Configuration Access Protocol - XCAP Diff"),
		array("mime_type" => "application/xenc+xml", "extension" => "xenc", "description" => "XML Encryption Syntax and Processing"),
		array("mime_type" => "application/patch-ops-error+xml", "extension" => "xer", "description" => "XML Patch Framework"),
		array("mime_type" => "application/resource-lists+xml", "extension" => "rl", "description" => "XML Resource Lists"),
		array("mime_type" => "application/rls-services+xml", "extension" => "rs", "description" => "XML Resource Lists"),
		array("mime_type" => "application/resource-lists-diff+xml", "extension" => "rld", "description" => "XML Resource Lists Diff"),
		array("mime_type" => "application/xslt+xml", "extension" => "xslt", "description" => "XML Transformations"),
		array("mime_type" => "application/xop+xml", "extension" => "xop", "description" => "XML-Binary Optimized Packaging"),
		array("mime_type" => "application/x-xpinstall", "extension" => "xpi", "description" => "XPInstall - Mozilla"),
		array("mime_type" => "application/xspf+xml", "extension" => "xspf", "description" => "XSPF - XML Shareable Playlist Format"),
		array("mime_type" => "application/vnd.mozilla.xul+xml", "extension" => "xul", "description" => "XUL - XML User Interface Language"),
		array("mime_type" => "chemical/x-xyz", "extension" => "xyz", "description" => "XYZ File Format"),
		array("mime_type" => "text/yaml", "extension" => "yaml", "description" => "YAML Ain't Markup Language / Yet Another Markup Language"),
		array("mime_type" => "application/yang", "extension" => "yang", "description" => "YANG Data Modeling Language"),
		array("mime_type" => "application/yin+xml", "extension" => "yin", "description" => "YIN (YANG - XML)"),
		array("mime_type" => "application/vnd.zul", "extension" => "zir", "description" => "Z.U.L. Geometry"),
		array("mime_type" => "application/zip", "extension" => "zip", "description" => "Zip Archive"),
		array("mime_type" => "application/vnd.handheld-entertainment+xml", "extension" => "zmm", "description" => "ZVUE Media Manager"),
		array("mime_type" => "application/vnd.zzazz.deck+xml", "extension" => "zaz", "description" => "Zzazz Deck"),
	);
	
	/**
	* isVideoMimeType: checks if mime type is video
	*/
	public static function isVideoMimeType($mime_type) {
		return strpos($mime_type, "video/") !== false || strpos($mime_type, "/x-shockwave-flash") !== false;
	}
	
	/**
	* isImageMimeType: checks if mime type is image
	*/
	public static function isImageMimeType($mime_type) {
		return strpos($mime_type, "image/") !== false;
	}
	
	/**
	* isTextMimeType: checks if mime type is text
	*/
	public static function isTextMimeType($mime_type) {
		return strpos($mime_type, "text/") !== false;
	}
	
	/**
	* isDocMimeType: checks if mime type is doc
	*/
	public static function isDocMimeType($mime_type) {
		switch ($mime_type) {
			case "application/msword":
			case "application/msword":
			case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
			case "application/vnd.openxmlformats-officedocument.wordprocessingml.template":
			case "application/vnd.ms-word.document.macroEnabled.12":
			case "application/vnd.ms-word.template.macroEnabled.12":
			case "application/vnd.ms-excel":
			case "application/vnd.ms-excel":
			case "application/vnd.ms-excel":
			case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
			case "application/vnd.openxmlformats-officedocument.spreadsheetml.template":
			case "application/vnd.ms-excel.sheet.macroEnabled.12":
			case "application/vnd.ms-excel.template.macroEnabled.12":
			case "application/vnd.ms-excel.addin.macroEnabled.12":
			case "application/vnd.ms-excel.sheet.binary.macroEnabled.12":
			case "application/vnd.ms-powerpoint":
			case "application/vnd.ms-powerpoint":
			case "application/vnd.ms-powerpoint":
			case "application/vnd.ms-powerpoint":
			case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
			case "application/vnd.openxmlformats-officedocument.presentationml.template":
			case "application/vnd.openxmlformats-officedocument.presentationml.slideshow":
			case "application/vnd.ms-powerpoint.addin.macroEnabled.12":
			case "application/vnd.ms-powerpoint.presentation.macroEnabled.12":
			case "application/vnd.ms-powerpoint.template.macroEnabled.12":
			case "application/vnd.ms-powerpoint.slideshow.macroEnabled.12":
				return true;
		}
		
		return strpos($mime_type, "/pdf") !== false;
	}
	
	/**
	* checkMimeType: checks if mime type is from a group of filters/types
	*/
	public static function checkMimeType($mime_type, $filters) {
		if ($filters) {
			if (is_array($filters)) {
				$status = false;
				
				$t = count($filters);
				for ($i = 0; $i < $t && !$status; $i++) {
					switch ($filters[$i]) {
						case "video": $status = self::isVideoMimeType($mime_type); break;
						case "image": $status = self::isImageMimeType($mime_type); break;
						case "text": $status = self::isTextMimeType($mime_type); break;
						case "doc": $status = self::isDocMimeType($mime_type); break;
						default: $status = $mime_type == $filters[$i];
					}
				}
				return $status;
			}
			
			switch ($filters) {
				case "video": return self::isVideoMimeType($mime_type);
				case "image": return self::isImageMimeType($mime_type);
				case "text": return self::isTextMimeType($mime_type);
				case "doc": return self::isDocMimeType($mime_type);
				default: $status = $mime_type == $filters;
			}
			
			return false;
		}
		
		return true;
	}
	
	/*
	 * getAvailableTypesByExtensions: gets file available extensions
	 */
	public static function getAvailableTypesByExtensions($filters = null) {
		$extensions = array();
		
		$t = count(self::$types);
		for ($i = 0; $i < count(self::$types); $i++) {
			$item = self::$types[$i];
			
			if (isset($item["mime_type"]) && self::checkMimeType($item["mime_type"], $filters)) {
				$extension = isset($item["extension"]) ? $item["extension"] : null;
				$parts = explode(" ", str_replace(array(",", ";"), " ", $extension));
				
				foreach ($parts as $part)
					if (trim($part))
						$extensions[$part] = $item;
			}
		}
		
		return $extensions;
	}
	
	/*
	 * getAvailableTypesByMimeType: gets file available mime types
	 */
	public static function getAvailableTypesByMimeType($filters = null) {
		$types = array();
		
		$t = count(self::$types);
		for ($i = 0; $i < count(self::$types); $i++) {
			$item = self::$types[$i];
			
			if (isset($item["mime_type"]) && self::checkMimeType($item["mime_type"], $filters))
				$types[ $item["mime_type"] ] = $item;
		}
		
		return $types;
	}
	
	/**
	* getAvailableFileExtensions: gets the available file extensions
	*/
	public static function getAvailableFileExtensions($filters = null) {
		$types = self::getAvailableTypesByExtensions($filters);
		return array_keys($types);
	}
	
	/**
	* getAvailableFileMimeTypes: gets the available file extensions
	*/
	public static function getAvailableFileMimeTypes($filters = null) {
		$types = self::getAvailableTypesByMimeType($filters);
		return array_keys($types);
	}
	
	/*
	 * getFileExtension: gets file extension
	 */
	public static function getFileExtension($file_path) {
		return pathinfo($file_path, PATHINFO_EXTENSION);
	}
	
	/*
	 * getFileMimeType: gets file mime type. 
	 * The file must exist!
	 */
	public static function getFileMimeType($file_path) {
		if ($file_path && file_exists($file_path)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $file_path);
			finfo_close($finfo);
			
			return $mime_type;
		}
	}
	
	/**
	* getTypeByExtension: gets type by extension
	*/
	public static function getTypeByExtension($extension, $filters = null) {
		$extensions = self::getAvailableTypesByExtensions($filters);
		$extension_lower = strtolower($extension);
		return isset($extensions[$extension_lower]) ? $extensions[$extension_lower] : null;
	}
	
	/**
	* getTypeByMimeType: gets type by mime type
	*/
	public static function getTypeByMimeType($mime_type, $filters = null) {
		$types = self::getAvailableTypesByMimeType($filters);
		$mime_type_lower = strtolower($mime_type);
		return isset($types[$mime_type_lower]) ? $types[$mime_type_lower] : null;
	}
	
	/*
	 * getFileTypeByExtension: gets file type by extension
	 */
	public static function getFileTypeByExtension($file_path, $filters = null) {
		$extension = self::getFileExtension($file_path);
		return self::getTypeByExtension($extension, $filters);
	}
	
	/*
	 * getFileTypeByMimeType: gets file type by mime type
	 */
	public static function getFileTypeByMimeType($file_path, $filters = null) {
		$mime_type = self::getFileMimeType($file_path);
		return self::getTypeByMimeType($mime_type, $filters);
	}
	
	/*
	 * getInvalidFileExtensions: gets the invalid file names
	 */
	public static function getInvalidFileExtensions() {
		return array(".", "..", ".svn");
	}
}
?>
