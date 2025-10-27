<?php

namespace Ycdev\PhpIgcInspector\Data;

/**
 * Classe contenant les codes fabricants IGC
 */
class ManufacturerCodesData
{
    /**
     * Tableau des codes fabricants reconnus par l'IGC
     * 
     * @var array<string, string> Code => Nom du fabricant
     */
    public static function getCodes(): array
    {
        return [
            // Fabricants IGC-approuvés (actifs)
            'ACT' => 'Aircotec',
            'AVX' => 'Avionix',
            'CNI' => 'ClearNav Instruments',
            'FIL' => 'Filser',
            'FLA' => 'Flarm (Flight Alarm)',
            'FLY' => 'Flytech',
            'GCS' => 'Garrecht',
            'IMI' => 'IMI Gliding Equipment',
            'LGS' => 'Logstream',
            'LXN' => 'LX Navigation',
            'LXV' => 'LXNAV d.o.o.',
            'NAV' => 'Naviter',
            'NKL' => 'Nielsen Kellerman',
            'PFE' => 'PressFinish Electronics',
            'RCE' => 'RC Electronics',
            'SDI' => 'Streamline Data Instruments',
            'TRI' => 'Triadis Engineering GmbH',
            
            // Fabricants IGC-approuvés (inactifs)
            'CAM' => 'Cambridge Aero Instruments',
            'DSX' => 'Data Swan/DSX',
            'EWA' => 'EW Avionics',
            'NTE' => 'New Technologies s.r.l.',
            'PES' => 'Peschges',
            'PRT' => 'Print Technik',
            'SCH' => 'Scheffel',
            'ZAN' => 'Zander',
            
            // Logiciels non IGC-approuvés (Windows)
            'XGD' => 'GPSDump',
            'XMP' => 'MaxPunkte',
            'XSY' => 'SeeYou (Naviter)',
            'XCG' => 'CompeGPS',
            'XTC' => 'TNComplete',
            'XPF' => 'ParaFlightBook',
            'XLF' => 'Logfly',
            
            // Logiciels non IGC-approuvés (Apple iPhone/Mac OSX)
            'XSL' => 'SkyLogger',
            'XSK' => 'SkyKick',
            'XTG' => 'Thermgeek',
            'XFH' => 'FlySkyhy',
            'XBA' => 'FreeFlight',
            'XNA' => 'SeeYou Navigator',
            'XFN' => 'ASI FlyNet2',
            'XRF' => 'RogalloFlightlog',
            'XGA' => 'FlyGaggle',
            'XWC' => 'White Cloud Blue Sky',
            'XVI' => 'Vario One',
            'XMX' => 'XCMania',
            'XBM' => 'burnair',
            
            // Logiciels non IGC-approuvés (Android)
            'XAF' => 'AndroFlight',
            'XCT' => 'XCTrack',
            'XCS' => 'XCSoar',
            'XFL' => 'FlyMe',
            'XKR' => 'Variometer-Sky Land Tracker',
            'XGP' => 'Flight GpsLogger',
            'XTT' => 'TTLiveTrack24',
            'XAA' => 'AltAir',
            'XAV' => 'Avionicus',
            'XMT' => 'MyCloudbase Tracker',
            'XLM' => 'Loctome',
            'XRV' => 'Aviator',
            'XIF' => 'XC Guide',
            'XPD' => 'Gleitschirm Cockpit',
            'XFV' => 'thefightvario',
            
            // Logiciels non IGC-approuvés (Linux)
            'XLK' => 'LK8000',
            
            // Logiciels non IGC-approuvés (Symbian)
            'XFT' => 'AFTrack',
            'XPY' => 'IGCLogger for Symbian',
            
            // Logiciels non IGC-approuvés (Mozilla Firefox)
            'XPG' => 'Gipsy',
            
            // Logiciels non IGC-approuvés (XC-Server)
            'XCF' => 'French C.F.D. contest Server',
            'XCO' => 'XCOpen Livetrack',
            'XLL' => 'Leonardo Livetrack24',
            'XLD' => 'DHV Livetracking',
            
            // Enregistreurs de position non IGC-approuvés (WXC)
            'XFW' => 'flyWithCE FR300',
            'XFM' => 'Flymaster Live',
            'XFI' => 'Flymaster GPS LS',
            'XMI' => 'MipFly Instruments',
            'XVB' => 'VairBration XC',
            
            // Autres instruments non IGC-approuvés
            'BRA' => 'Bräuniger Logger',
            'FLY' => 'Flytec Logger',
            'XSX' => 'Skytraxx Logger',
            'MUN' => 'MaxLogger',
            'CPP' => 'C-Pilot pro Logger',
            'XRE' => 'REVERSALE VGP2010',
            'XDG' => 'Digifly Instruments',
            'XFY' => 'SensBox',
            'XSR' => 'Syride SysPCTools',
            'XSE' => 'Syride SYS\'Evolution',
            'XFX' => 'FlyNet XC vario',
            'XAH' => 'Ascent Vario',
            'XSF' => 'SeriFly',
            'XTR' => 'XCTracer',
            'XSB' => 'SkyBean vario',
            'XSD' => 'leGPSBip Logger',
            'XGF' => 'GoFly Instrument',
            'XUR' => 'Renschler Solario Blue',
            'XEP' => 'EpVario OpenSource',
            'XBF' => 'Blue Fly Vario',
            
            // En cours d'investigation
            'XCA' => 'XC Analytics',
        ];
    }
}
