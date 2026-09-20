<?php

namespace Database\Seeders;

use App\Enums\CategoriaTecnica;
use App\Enums\ModalidadTecnica;
use App\Enums\NivelEntrenamiento;
use App\Models\Tecnica;
use Illuminate\Database\Seeder;

/**
 * Biblioteca de técnicas del currículo ATA (catálogo compartido). Portado desde
 * los manuales traducidos: Patadas (tradicionales por cinturón + Creative/Xtreme
 * de ATA MAX), Tricks, Manos y Armas (con sus segmentos). Las FORMAS ya no son
 * técnicas: viven en `formas`/`pasos_forma` (FormasPasosSeeder).
 *
 * Idempotente. Las combinaciones/armas con secuencia guardan sus pasos en
 * pasos_tecnica.
 */
class TecnicasSeeder extends Seeder
{
    public function run(): void
    {
        $this->patadasTradicionales();
        $this->patadasMax();
        $this->tricks();
        $this->manos();
        $this->armas();
        // Las formas ya NO son técnicas: viven en `formas`/`pasos_forma`
        // (FormasPasosSeeder). Ver la migración que separa formas de técnicas.
    }

    /**
     * Crea (idempotente) una técnica y opcionalmente sus pasos.
     *
     * @param  array<int, array{0: ?string, 1: string}>  $pasos  [segmento, texto]
     */
    private function tecnica(CategoriaTecnica $cat, string $nombre, array $attrs = [], array $pasos = []): void
    {
        $tecnica = Tecnica::updateOrCreate(
            ['categoria' => $cat->value, 'nombre' => $nombre],
            array_merge(['core' => true], $attrs),
        );

        if ($pasos) {
            $tecnica->pasos()->delete();
            foreach ($pasos as $i => [$segmento, $texto]) {
                $tecnica->pasos()->create(['segmento' => $segmento, 'texto' => $texto, 'orden' => $i + 1]);
            }
        }
    }

    // ── Patadas tradicionales (por cinturón) ────────────────────────────────

    private function patadasTradicionales(): void
    {
        $prin = NivelEntrenamiento::Principiantes->value;
        $inter = NivelEntrenamiento::Intermedio->value;
        $avan = NivelEntrenamiento::Avanzado->value;
        $dan = NivelEntrenamiento::Danes->value;

        // [cinturón, nivel, patadas]
        $porCinturon = [
            ['Blanco', $prin, 'Patada lateral n.º 1, n.º 2 y n.º 3'],
            ['Naranjo', $prin, 'Patada circular n.º 1, n.º 2 y n.º 3'],
            ['Amarillo', $prin, 'Patada frontal n.º 1, n.º 2 y n.º 3 · Patada frontal en salto n.º 1, n.º 2 y n.º 3'],
            ['Camuflaje', $prin, 'Patada lateral invertida · Patada lateral invertida con paso'],
            ['Verde', $inter, 'Patada lateral n.º 1, n.º 2 y n.º 3 · Patada lateral en salto n.º 1, n.º 2 y n.º 3'],
            ['Morado', $inter, 'Patada creciente interna n.º 1, n.º 2 y n.º 3 · Patada creciente externa n.º 1, n.º 2 y n.º 3 · Patada creciente externa con giro · con giro y paso · Patada mariposa'],
            ['Azul', $avan, 'Patada de talón con giro · con giro y paso · Patada de hacha n.º 1, n.º 2 y n.º 3'],
            ['Marrón', $avan, 'Patada creciente externa en salto n.º 1, n.º 2 y n.º 3 · con giro en salto · con giro en salto y paso · Patada lateral invertida en salto · en salto y paso'],
            ['Rojo', $avan, 'Patada de gancho en salto n.º 1, n.º 2 y n.º 3 · Patada circular en salto n.º 1, n.º 2 y n.º 3 · Patada de gancho con giro en salto · con giro en salto y paso'],
            ['1.er Dan (recomendado)', $dan, 'Patada de gancho n.º 1, n.º 2 y n.º 3 · Patada de gancho con giro · con giro y paso'],
            ['1.er Dan (decidido)', $dan, 'Patada de talón con giro en salto · con giro en salto y paso · Patada de hacha en salto n.º 1, n.º 2 y n.º 3'],
            ['2.º Dan (decidido)', $dan, 'Patada torcida (twist) n.º 1, n.º 2 y n.º 3 · Patada torcida en salto n.º 1, n.º 2 y n.º 3'],
        ];

        foreach ($porCinturon as $i => [$cinturon, $nivel, $patadas]) {
            $this->tecnica(CategoriaTecnica::Patada, 'Patadas de cinturón '.$cinturon, [
                'subcategoria' => 'Por cinturón',
                'descripcion' => $patadas.' — a demostrar con ambos lados (derecho e izquierdo).',
                'modalidad' => ModalidadTecnica::Tradicional->value,
                'cinturon' => $cinturon,
                'nivel' => $nivel,
                'core' => true,
                'orden' => $i + 1,
            ]);
        }
    }

    // ── Patadas ATA MAX (Creative / Xtreme) ─────────────────────────────────

    private function patadasMax(): void
    {
        // [nombre, subcategoria, modalidad, descripción]
        $max = [
            ['Auto Bahn', 'Creative', ModalidadTecnica::Creative, 'Spin Hook Kick a Tornado Kick y a un Hop Over Spin Hook.'],
            ['Front Sweep', 'Creative', ModalidadTecnica::Creative, 'Con la pierna de patear atrás, apoya mano y rodilla delanteras en el suelo; en continuo, la pierna trasera ejecuta un Round o Inside Crescent Kick y empuja el suelo para volver a la posición de combate.'],
            ['Back Sweep', 'Creative', ModalidadTecnica::Creative, 'Similar a un Spin Hook pero a ras de suelo: gira defensivamente, apoya ambas manos y ejecuta un Heel o Hook Kick; empuja el suelo para volver.'],
            ['Pop 360', 'Pop', ModalidadTecnica::Xtreme, 'Salta con ambas piernas girando defensivamente y completa 360° ejecutando un Outside Crescent Kick.'],
            ['Pop 360 Feilong', 'Pop', ModalidadTecnica::Xtreme, 'Como el Pop 360, pero al girar 360° ejecuta un Outside Spin Kick y, antes de aterrizar, un Inside Kick (dos patadas “outside a inside”).'],
            ['Pop 720', 'Pop', ModalidadTecnica::Xtreme, 'Salta con ambas piernas girando defensivamente y completa 720° ejecutando un Outside Spin Kick.'],
            ['Tornado Kick (Butterfly)', 'Cheat', ModalidadTecnica::Xtreme, 'Desde posición lista, gira defensivamente; prepara como Outside Spin Kick pero cambia y patea con la pierna contraria. En ATA se conoce como Butterfly Kick.'],
            ['Cheat 720', 'Cheat', ModalidadTecnica::Xtreme, 'Con despegue de una pierna como el Tornado; salta y gira, y al ver el blanco dos veces ejecuta un Outside Kick.'],
            ['Cheat 540', 'Cheat', ModalidadTecnica::Xtreme, 'Despegue de una pierna; levanta la trasera apuntando al blanco, cambia y patea con la contraria, aterrizando solo sobre la pierna de patear.'],
            ['Backside 900', 'Backside', ModalidadTecnica::Xtreme, 'Desde postura backside, salta con ambas piernas y gira ofensivamente una vez; patea con la que era trasera.'],
            ['Backside 1080', 'Backside', ModalidadTecnica::Xtreme, 'Desde postura backside, salta con ambas piernas y gira ofensivamente dos veces; patea con la que era delantera.'],
        ];

        foreach ($max as $i => [$nombre, $sub, $modalidad, $desc]) {
            $this->tecnica(CategoriaTecnica::Patada, $nombre, [
                'subcategoria' => $sub,
                'descripcion' => $desc,
                'modalidad' => $modalidad->value,
                'core' => false,
                'orden' => 100 + $i,
            ]);
        }
    }

    // ── Tricks (ATA MAX) ────────────────────────────────────────────────────

    private function tricks(): void
    {
        $tricks = [
            ['Cartwheel', 'Flip', 'Manos al suelo una a la vez; las piernas pasan por encima del cuerpo y aterrizan una a la vez.'],
            ['Roundoff', 'Flip', 'Como el Cartwheel, pero aterrizando con ambas piernas a la vez.'],
            ['Ariel', 'Flip', 'Cartwheel sin manos: el tronco se inclina sobre el pie delantero y patea por encima de la pierna trasera; aterriza primero sobre la trasera.'],
            ['Gainer', 'Flip', 'Back flip de una pierna, normalmente desde un paso de pivote; la pierna que osciló es también la de aterrizaje.'],
            ['Webster', 'Flip', 'Front flip de una pierna: la trasera patea hacia atrás por encima de la cabeza mientras el cuerpo se lanza adelante; aterriza con ambos pies.'],
            ['Scoot', 'Transición', 'Desde una rodilla, extiende la mano del mismo lado al suelo; salta poniendo el peso sobre la mano y aterriza primero con la pierna que estaba en la rodilla.'],
            ['Raiz', 'Transición', 'Desde el set-up de cheat, como un Tornado invertido: la segunda pierna se eleva con el cuerpo mirando al suelo; despega de la primera girando defensivamente para aterrizar sobre la segunda.'],
            ['One-Handed Raiz', 'Transición', 'Como el Raiz, tocando el suelo con el segundo brazo justo antes de que aterrice la segunda pierna.'],
            ['Gumbi', 'Transición', 'Girando defensivamente sobre un pie, en continuo y sin apoyar el pie, entra en un cartwheel.'],
            ['Wushu Butterfly Kick', 'Twist', 'Paso atrás; baja y sube el tronco en “U” transfiriendo el pecho de una pierna a la otra; patea con la trasera logrando un giro plano y aterriza sobre la que pateó.'],
            ['Illusion Twist', 'Twist', 'Primera mitad del Wushu Butterfly y, al elevarse pierna y cuerpo, continúa en un Pop 360.'],
            ['B-Twist', 'Twist', 'Primera mitad del Wushu Butterfly y, con pierna y cuerpo paralelos al suelo, gira el cuerpo 360° (giro plano por el plano transverso).'],
        ];

        foreach ($tricks as $i => [$nombre, $sub, $desc]) {
            $this->tecnica(CategoriaTecnica::Trick, $nombre, [
                'subcategoria' => $sub,
                'descripcion' => $desc,
                'modalidad' => ModalidadTecnica::Xtreme->value,
                'core' => false,
                'orden' => $i + 1,
            ]);
        }
    }

    // ── Manos (ATA MAX, combinaciones con pasos) ────────────────────────────

    private function manos(): void
    {
        // [nombre, familia, pasos[]]
        $combos = [
            ['Spin Combo 1', 'Spin', ['[Spin] – High Block (corner)', 'Punch', 'Ridge Hand–Knife Hand Strike', 'Punch']],
            ['Spin Combo 2', 'Spin', ['[Spin] – Chop (corner)', 'Shift to Under Chop', 'Chop', 'Punch']],
            ['Spin Combo 3', 'Spin', ['[Spin] – Chop (back corner)', 'Shift to Inner Forearm Block', 'Spin Setup for Kick, Trick, or Finish']],
            ['Switch Combo 1', 'Switch', ['[Switch] – Chop to Punch (corner)', 'Step to Center Ridge Hand–Knife Hand Strike', 'Switch Punch']],
            ['Switch Combo 2', 'Switch', ['[Switch] – Upward Elbow', 'Switch Punch', 'Under Chop to Corner', 'Chop', 'Switch Punch (corner)']],
            ['Switch Combo 3', 'Switch', ['[Switch] – Back Elbow (corner)', 'Switch Punch', 'Shift to Horizontal Elbow to center', 'Step Punch', 'Setup for Kick, Trick, or Finish']],
            ['Level Change Combo 1', 'Level Change', ['Chop (standing to corner)', 'Downward Ridge Hand to a Knee', 'Ridge Hand–Knife Hand Strike', 'Punch']],
            ['Level Change Combo 2', 'Level Change', ['Chop (standing to corner)', 'Under Chop', 'Chop', 'Supported Vertical Punch to center to a Knee']],
            ['Level Change Combo 3', 'Level Change', ['Chop (standing to center)', 'Punch', 'Spear Hand to Knee', 'Stand Up High Low Block', 'Setup for Kick, Trick, or Finish']],
        ];

        foreach ($combos as $i => [$nombre, $familia, $pasos]) {
            $this->tecnica(CategoriaTecnica::Mano, $nombre, [
                'subcategoria' => $familia,
                'descripcion' => 'Combinación de manos ATA MAX. Verificar el orden exacto contra el documento original.',
                'modalidad' => ModalidadTecnica::Xtreme->value,
                'core' => false,
                'orden' => $i + 1,
            ], array_map(fn ($p) => [null, $p], $pasos));
        }
    }

    // ── Armas (ATA MAX, con segmentos) ──────────────────────────────────────

    private function armas(): void
    {
        // [nombre, arma, segmentos: [segmento => pasos[]]]
        $armas = [
            ['Jahng Bong — Release', 'Jahng Bong', [
                'Segmento 1' => ['Forward figure 8', 'Punch', 'Box strikes', 'Reverse figure 8', 'Punch', 'Behind the back to toss and catch'],
                'Segmento 2' => ['Spinning forward figure 8 to reverse figure 8', 'Punch', 'Middle stance over head line 4,3,4', 'Over head line 1 – follow through strike into front stance', 'Right hand box cutter release', 'Inner forearm block'],
                'Segmento 3' => ['High block vertical punch', 'Over head low block', 'Circle line 7', 'Over the head line 4', 'Toss to behind the back catch', 'Spin to finish'],
            ]],
            ['Jahng Bong — Roll', 'Jahng Bong', [
                'Segmento 1' => ['Full neck roll', 'Figure 8', 'Punch', 'Reverse figure 8', 'Punch', 'Outside hand roll (continúa)'],
                'Segmento 2' => ['Palm spin', 'Middle stance over the head line 4,3,4', 'Over head line 1 – follow through into front stance', 'Side inside hand roll', 'Inner forearm block'],
                'Segmento 3' => ['High block vertical punch', 'Over head low block', 'Circle line 7', 'Step forward line 4', 'Front neck roll', 'Spin to stab & finish'],
            ]],
            ['Ssahng Jeol Bong — Release', 'Ssahng Jeol Bong', [
                'Segmento 1' => ['Double strike', 'Double strike', 'Ladder strikes', 'Ladder strike', 'Single toss catch vertical', 'Double strike'],
                'Segmento 2' => ['Ladder strike', 'Ladder strike', 'V strike', 'Ladder V strike', 'Foot Toss', 'Double strike'],
                'Segmento 3' => ['Double hip circle strike', 'Double triangle strikes', 'Low double figure 8 fusion toss', 'Finish'],
            ]],
            ['Ssahng Jeol Bong — Roll', 'Ssahng Jeol Bong', [
                'Segmento 1' => ['Double strike', 'Double strike', 'Ladder strikes', 'Ladder strike', 'Low-high hand roll', 'Single strike', 'Double strike', 'High-low cheat jump spin', 'Double strike'],
                'Segmento 2' => ['Ladder strikes', 'Ladder strikes', 'V strike', 'Ladder V strike', 'Double strike', 'Thumb spin', 'Double strike'],
                'Segmento 3' => ['Double hip circle strike', 'Double triangle strike', 'Figure 8', 'Finish'],
            ]],
            ['Ssahng Nat', 'Ssahng Nat', [
                'Segmento 1' => ['Chop (corner)', 'Punch', 'Cutting strike to center', 'Punch', 'Figure 8', 'Twin punch to a knee'],
                'Segmento 2' => ['1st Hand over the head finger roll', '2nd Hand over the head finger roll', 'Chop (corner)', 'Punch', 'Down cut to a knee to center'],
                'Segmento 3' => ['Box cutter toss', 'Twin cutting strike', 'Under arm toss', 'High-low block to a knee', 'Finish'],
            ]],
            ['Gum Do', 'Gum Do', [
                'Segmento 1' => ['Line 1', 'Line 2', 'Line 5 to a knee', 'Stand line 4', 'Wrist roll to inside toss', 'Line 1'],
                'Segmento 2' => ['Single hand line 6', 'Figure 8', 'Line 5', 'Line 6', 'Line 3 to knee', 'Stand up box cutter toss', 'Over the head palm up stab'],
                'Segmento 3' => ['Over head to line 2', 'Fan strike', 'Line 2', 'Over head step to middle stance line 1', 'Stab', 'Toss to cradle finish on a knee'],
            ]],
        ];

        foreach ($armas as $i => [$nombre, $arma, $segmentos]) {
            $pasos = [];
            foreach ($segmentos as $segmento => $items) {
                foreach ($items as $item) {
                    $pasos[] = [$segmento, $item];
                }
            }

            $this->tecnica(CategoriaTecnica::Arma, $nombre, [
                'subcategoria' => $arma,
                'descripcion' => 'Fórmula de arma ATA MAX. Verificar el orden de los segmentos contra el documento original.',
                'modalidad' => ModalidadTecnica::Xtreme->value,
                'core' => false,
                'orden' => $i + 1,
            ], $pasos);
        }
    }
}
