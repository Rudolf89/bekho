import { useState } from "react";

// ── CALENTAMIENTO ─────────────────────────────────────────────────────────────
const warmupCats = [
  { id:"guardia", label:"🔄 Cambios de Guardia y Desplazamientos", color:"#0077b6", exercises:[
    { name:"Cambio de guardia básico", desc:"Guardia izquierda → derecha → izquierda. x8." },
    { name:"Adelante – atrás – cambio de guardia", desc:"Paso adelante, paso atrás, cambio de guardia. x8 cada lado." },
    { name:"Lateral – lateral – cambio de guardia", desc:"Paso lateral derecha, izquierda, cambio de guardia. x8." },
    { name:"Desplazamiento en L", desc:"Diagonal derecha, diagonal izquierda, cambio de guardia. x6." },
    { name:"Avanzar y retroceder x4 + cambio", desc:"4 pasos adelante + 4 atrás + cambio de guardia. x4 series." },
    { name:"Círculo con cambio de guardia", desc:"Desplazarse en círculo cambiando guardia cada 2 pasos. Ambas direcciones." },
  ]},
  { id:"punos", label:"👊 Puños y Combinaciones de Manos", color:"#c1440e", exercises:[
    { name:"Puño – Puño reverso", desc:"Puño directo + puño reverso. x10 por lado." },
    { name:"Puño – Puño reverso – Puño", desc:"Combinación 3 tiempos. Retracción rápida. x8 por lado." },
    { name:"Doble puño avanzando", desc:"Paso adelante + doble puño (izq–der). Retroceder + doble puño. x8." },
    { name:"Puño bajo – puño alto", desc:"Puño solar plexus + puño cabeza. Alternar lados. x10 por lado." },
    { name:"Puño reverso – cambio – puño reverso", desc:"Puño reverso derecho, cambio de guardia, puño reverso izquierdo. x8." },
    { name:"3 puños rápidos + cambio de guardia", desc:"3 puños continuos rápidos + cambio de guardia. x6 series." },
    { name:"Puños continuos x10 + cambio", desc:"10 puños alternados rápidos + cambio de guardia. x4 series." },
  ]},
  { id:"combopunos", label:"🥊 Combinaciones de Puños", color:"#b5179e", exercises:[
    { name:"Combinación 1 – Puño de adelante", desc:"Puño directo con la mano delantera. Retracción completa. x10 por lado." },
    { name:"Combinación 2 – Puño / Puño reverso", desc:"Puño directo (mano delantera) + puño reverso (mano trasera). x8 por lado." },
    { name:"Combinación 3 – Puño reverso / Gancho / Puño reverso", desc:"Puño reverso + gancho + puño reverso. Guardia alta entre golpes. x6 por lado." },
    { name:"Combinación 4 – Puño / Puño reverso / Gancho / Puño reverso", desc:"4 tiempos completos. Primero lento con forma, luego a velocidad. x6 por lado." },
    { name:"Combinación 4 avanzando", desc:"Comb. 4 con paso adelante al iniciar y paso atrás al finalizar. x6 series." },
    { name:"Combinación 4 ×2 + cambio de guardia", desc:"Comb. 4 dos veces seguidas + cambio de guardia + repetir del otro lado. x4 series." },
  ]},
  { id:"piernas", label:"🦵 Sentadillas y Levantamiento de Piernas", color:"#2d6a4f", exercises:[
    { name:"Sentadilla + patada de frente", desc:"Sentadilla → levantarse → patada de frente. Alternar piernas. x8 por lado." },
    { name:"Sentadilla + rodilla al pecho", desc:"Sentadilla → subir rodilla al pecho alternada. x10." },
    { name:"Sentadilla + levantamiento lateral", desc:"Sentadilla → levantamiento de pierna lateral. x8 por lado." },
    { name:"Sentadilla sumo + doble rodilla", desc:"Pies abiertos → al subir, rodilla derecha + izquierda alternadas. x8." },
    { name:"Levantamiento recto de pierna", desc:"Pierna recta al frente, espalda erguida, sin doblar rodilla. x10 por lado. Progresión: lento con pausa → dinámico." },
    { name:"Levantamiento circular hacia adentro", desc:"Pierna recta al frente trazando semicírculo hacia adentro hasta el lateral. x8 por lado." },
    { name:"Levantamiento circular hacia afuera", desc:"Pierna recta al lateral trazando semicírculo hacia afuera hasta el frente. x8 por lado." },
    { name:"Circular adentro + afuera continuo", desc:"Sin bajar la pierna: circular adentro → circular afuera en continuo. x6 por lado." },
    { name:"Rodillas circulares adentro", desc:"Levantar rodilla y hacer círculo completo hacia adentro antes de bajar. x8 por lado." },
    { name:"Rodillas circulares afuera", desc:"Levantar rodilla y hacer círculo completo hacia afuera antes de bajar. x8 por lado." },
  ]},
  { id:"combo", label:"💥 Combinaciones Mixtas", color:"#7b2d8b", exercises:[
    { name:"Puño – Puño reverso – Patada de frente", desc:"Puño directo + reverso + patada de frente con pierna trasera. x6 por lado." },
    { name:"Cambio de guardia – Puño – Patada lateral", desc:"Cambio de guardia + puño directo + patada lateral. x6 por lado." },
    { name:"2 Puños – Sentadilla – Cambio de guardia", desc:"Doble puño + sentadilla + cambio de guardia + doble puño. x8 series." },
    { name:"Avanzar – Puño – Retroceder – Puño reverso", desc:"Paso adelante + puño directo + paso atrás + puño reverso. x8." },
    { name:"Sentadilla – Puño – Puño reverso – Patada", desc:"Sentadilla → levantarse → puño + reverso → patada de frente. x6 por lado." },
    { name:"3 pasos + ráfaga de puños + cambio", desc:"Avanzar 3 pasos + 5 puños rápidos + cambio de guardia + retroceder. x4 series." },
    { name:"Rodilla – Patada de frente – Puño", desc:"Rodilla al pecho + extender en patada de frente + puño al bajar. x6 por lado." },
  ]},
  { id:"cardio", label:"🔥 Cardio y Resistencia", color:"#f77f00", exercises:[
    { name:"Burpee + cambio de guardia", desc:"Burpee completo → cambio de guardia + 2 puños. x8." },
    { name:"Burpee + patada de frente", desc:"Burpee completo → patada de frente alternada. x8." },
    { name:"Saltos en guardia", desc:"Saltos cortos alternando pie delantero/trasero rápidamente. 20 seg + pausa. x4." },
    { name:"Carrera en sitio + cambio de guardia", desc:"Trotar en sitio x10 seg + cambio de guardia súbito. x6." },
    { name:"Combinación puños + patadas + burpee", desc:"5 puños alternados + patada frente derecha + izquierda + 1 burpee. x6." },
    { name:"Saltar y cambiar de guardia", desc:"Salto cambiando pies en el aire. Aterrizar en guardia opuesta. x10." },
  ]},
];

const warmupFilters = { tigers:["guardia","punos","piernas"], kids:["guardia","punos","combopunos","piernas","combo"], adults:["guardia","punos","combopunos","piernas","combo","cardio"] };
const warmupNotes = { tigers:"Usar solo Comb. 1 y 2 de puños. Omitir burpees y ganchos. Conteo cantado y música.", kids:"Comb. 1 a 4 según cinturón. Primero lento con forma, luego a velocidad.", adults:"Todas las categorías. Énfasis en retracción, velocidad y cambio de guardia entre combos." };

// ── LECCIÓN DE VIDA ───────────────────────────────────────────────────────────
const lecciones = [
  {
    semana:7, tema:"Disciplina", color:"#1a1a2e",
    comienzo:{ texto:"VISIÓN es uno de los pilares más importantes de disciplina. Los alumnos disciplinados siempre tendrán presente su visión para alcanzar sus objetivos. ¿Cuál es su objetivo hoy?", frase:"VISUALICE SUS OBJETIVOS" },
    durante:{ texto:"Se acerca el examen, así que visualicemos cómo se ve un campeón durante un examen. Un campeón es fuerte, confiado y tiene un alto nivel de disciplina.", frase:"VISUALICE SUS LOGROS" },
    fin:{ texto:"Un líder en la casa siempre tiene la visión de cómo debe comportarse, cómo debe lucir su habitación e incluso qué tan bien le va a ir en la escuela. ¿Quién usa visualización en casa?", frase:"PONGA SU VISIÓN EN ACCIÓN" },
  },
];

// ── PLANNER REGULARES ─────────────────────────────────────────────────────────
const levels = {
  beginners:    { label:"Beginners",    color:"#0077b6", formula:"Songahm 3 (Paso 14)",    defensa:"Grado 9 – N°3", patadas:"Circular Afuera 1,2,3,4 / Patada de Frente Saltando 1,2,3,4", combinaciones:["Circular A.3 – Vuelta 2","P. de Frente S.3 – Circular A.3","P. de Frente S.2 – Circular A.3","Circular A.1 – P. de Frente S.2"], roturas:"Combinación opcional de períodos anteriores" },
  intermediate: { label:"Intermediate", color:"#2d6a4f", formula:"In-Wha 1 (Paso 24)",      defensa:"Grado 8 – N°3", patadas:"Gancho 1,2,3,4 / Giro Gancho A,B,C,D",                         combinaciones:["Gancho 3 – Giro Gancho A","Giro Gancho D – P. de Costado 3","Gancho 1 – Giro Gancho C","Vuelta P.2 Repetida – Giro Gancho A"], roturas:"Combinar períodos anteriores de manera opcional" },
  advanced:     { label:"Advanced",     color:"#c1440e", formula:"Choong Jung 1 (Paso 22)", defensa:"Grado 7 – N°3", patadas:"Gancho Saltando 1,2,3,4 / Giro Gancho Saltando A,B,C,D / Giro Mariposa", combinaciones:["Gancho S.3 – Giro Mariposa","Giro Gancho S.A – Gancho S.3","Giro Mariposa con paso – Giro Gancho C"], roturas:"1 rotura de mano + 1 de pie libre (decisión instructor–alumno)" },
};

const buildSchedule = (lvl, group) => {
  const {formula:f,defensa:d,patadas:p,combinaciones:c,roturas:r} = lvl;
  const combosStr = c.map((x,i)=>`${i+1}. ${x}`).join("  |  ");
  const s = {
    tigers:[
      {time:"0–5 min",  block:"🔥 Warm Up",                       detail:"Ronda circular con música: cambios de guardia cantados, saltos, aplaudir en guardia. Máx. 2 ejercicios seguidos. Saludo grupal final.",                                                              cuadrante:"Estructura"},
      {time:"5–12 min", block:`⭐ Fórmula: ${f}`,                  detail:"Instructor al frente como espejo. Contar en coreano con palmadas. Solo pasos ya aprendidos. Aplausos por logros.",                                                                                    cuadrante:"Memorización / Emoción"},
      {time:"12–20 min",block:"🥋 Defensa y Ataque",               detail:`${d}. Movimientos con imagen visual. Escudos de colores. Siempre dirigido por el instructor.`,                                                                                                        cuadrante:"Memorización"},
      {time:"20–28 min",block:"🦵 Patadas y Combinaciones",         detail:`Solo patada de frente y circular básica con apoyo en barra. Demostración breve de la combinación más simple del nivel.`,                                                                              cuadrante:"Postura / Equilibrio"},
      {time:"28–33 min",block:"💥 Roturas",                         detail:"1 técnica sencilla con foam. Cada alumno rompe con ayuda y recibe aplausos.",                                                                                                                         cuadrante:"Foco / Confianza"},
      {time:"33–39 min",block:"⚔️ Juego de Golpes",                detail:"Instructor sostiene escudo, alumno golpea según color/número. Sin armas ni sparring libre.",                                                                                                          cuadrante:"Velocidad / Reacción"},
      {time:"39–42 min",block:"🥊 Mini Sparring",                   detail:"Juego de tocar hombro/rodilla. 30 seg por turno. Supervisión directa.",                                                                                                                               cuadrante:"Coordinación"},
      {time:"42–45 min",block:"🏆 Cierre y Premio",                 detail:"Sello o sticker de esfuerzo. Instructor menciona 1 logro de cada niño. Despedida con saludo formal en coreano.",                                                                                     cuadrante:"Legado"},
    ],
    kids:[
      {time:"0–7 min",  block:"🔥 Warm Up",                       detail:"Cambios de guardia, movilidad articular básica, desplazamientos en sparring, 10 sentadillas + puños, combinación puños + patada al frente + burpee. Dirigido por un alumno líder.",                   cuadrante:"Estructura"},
      {time:"7–14 min", block:`⭐ Fórmula: ${f}`,                  detail:"Fórmula completa en grupo, luego segmentos por tiempo (30 seg c/u). Regular: hasta la mitad. Leadership: completa.",                                                                                  cuadrante:"Memorización / Balance"},
      {time:"14–21 min",block:"🥋 Defensa y Ataque",               detail:`${d} – Parejas con roles definidos (ataca/defiende). Luego con paleta o escudo. Énfasis en control de distancia.`,                                                                                   cuadrante:"Control / Velocidad"},
      {time:"21–27 min",block:"🏹 Armas SJB/BME",                  detail:"Fórmula simple supervisada. Ángulos y movimientos específicos. Leadership: también dobles.",                                                                                                          cuadrante:"Coordinación / Intensidad"},
      {time:"27–34 min",block:"🦵 Patadas y Combinaciones",         detail:`Patadas: ${p}. 4 etapas con paleta en parejas.\nCombinaciones: ${combosStr}. Practicar cada combo x4 alternando piernas.`,                                                                           cuadrante:"Clavado / Látigo"},
      {time:"34–37 min",block:"💥 Roturas",                         detail:`${r}. 1–2 técnicas. Foam o plástico según cinturón. Compañeros evalúan.`,                                                                                                                            cuadrante:"Precisión / Foco"},
      {time:"37–42 min",block:"⚔️ Combat Weapon + Sparring",        detail:"Movilidad 1,2,3 golpes afuera/adentro 3 zonas. Sparring al punto (2 min) con rotación de parejas.",                                                                                                  cuadrante:"Timing / Amagues"},
      {time:"42–45 min",block:"🏆 Anuncios y Premios",              detail:"Reconocimiento del alumno destacado. Nominaciones a examen. Anuncio de torneos.",                                                                                                                     cuadrante:"Legado"},
    ],
    adults:[
      {time:"0–8 min",  block:"🔥 Warm Up",                       detail:"Cambios de guardia, movilidad articular completa, desplazamientos en sparring, sentadillas y puños, levantamiento de pierna recto y lateral, rodillas circulares adentro/afuera, puños + patadas + burpee.", cuadrante:"Estructura"},
      {time:"8–15 min", block:`⭐ Fórmula: ${f}`,                  detail:"Fórmula completa. Énfasis en torsión/clavado y cadera/posiciones. Grupal → individual con autoevaluación. Leadership: autocorrección de postura.",                                                     cuadrante:"Torsión / Cadera"},
      {time:"15–22 min",block:"🥋 Defensa y Ataque",               detail:`${d} – Libre en parejas con velocidad real. Ataque–defensa–contraataque. Con paleta o escudo libre.`,                                                                                                 cuadrante:"Velocidad / Foco"},
      {time:"22–28 min",block:"🏹 Armas SJB/BME (Completa)",       detail:"Fórmula completa, ángulos, movimientos específicos. Segmentos cronometrados. Coordinación → Velocidad → Intensidad → Poder.",                                                                         cuadrante:"Coordinación / Poder"},
      {time:"28–35 min",block:"🦵 Patadas y Combinaciones",         detail:`Patadas: ${p}. 4 etapas completas con compañero.\nCombinaciones: ${combosStr}. Cada combo x6 con corrección técnica del instructor.`,                                                                cuadrante:"Pivot / Postura"},
      {time:"35–38 min",block:"💥 Roturas",                         detail:`${r}. Tablillas reales según nivel. Velocidad de ejecución y distancia correcta.`,                                                                                                                   cuadrante:"Velocidad / Distancia"},
      {time:"38–43 min",block:"⚔️ Combat Weapon + Sparring",        detail:"Movilidad 1,2,3 golpes afuera/adentro 3 zonas. Sparring continuo, de examen o exhibición según semana.",                                                                                             cuadrante:"Timing / Ataques y Defensas"},
      {time:"43–45 min",block:"🏆 Anuncios",                        detail:"Nominación a exámenes. Retroalimentación técnica individual breve.",                                                                                                                                  cuadrante:"Legado"},
    ],
  };
  return s[group];
};

const cuadColor = {"Estructura":"#0077b6","Memorización / Emoción":"#0096c7","Memorización":"#0096c7","Memorización / Balance":"#0096c7","Torsión / Cadera":"#0096c7","Postura / Equilibrio":"#e76f51","Control / Velocidad":"#48cae4","Velocidad / Foco":"#48cae4","Velocidad / Reacción":"#48cae4","Coordinación / Intensidad":"#f77f00","Coordinación / Poder":"#f77f00","Coordinación":"#f77f00","Clavado / Látigo":"#d62828","Pivot / Postura":"#d62828","Precisión / Foco":"#7b2d8b","Foco / Confianza":"#9b59b6","Velocidad / Distancia":"#7b2d8b","Timing / Amagues":"#2d6a4f","Timing / Ataques y Defensas":"#2d6a4f","Legado":"#6c757d"};

// ── BLACK BELT ────────────────────────────────────────────────────────────────
const bbWeeks = [
  { id:"s12",label:"Sem. 1 & 2",tema:"Velocidad / Explosión",icon:"⚡",color:"#1d3557", warmupGeneral:["Puños rectos continuos a velocidad máxima","Ganchos alternados izquierda / derecha","Upper cut doble + cambio de guardia","Combinación: recto – gancho – upper cut x4"], warmupEsp:["Sparring Combos al aire: Combo 1, 2, 3 y 4 en secuencia","Mismo trabajo con paletas en parejas"], basicos:["Básicos: B.A. Fórmula (énfasis en velocidad)","En pareja con paletas – respuesta rápida al blanco","Con escudos – potencia + velocidad en cada golpe"], sparring:["Combo 1 al aire","Combo 2 al aire / paletas","Combo 3 al aire / paletas","Combo 4 al aire / paletas"], anuncios:["Nominación Exámenes","Torneo Nacional"], adapt:{tigers:"Solo Combo 1 y 2. Upper cut como demostración. Paletas bajas.",kids:"Combos 1 al 4 según cinturón. Paletas supervisadas. Corrección de retracción.",adults:"Los 4 combos a máxima velocidad. Paletas + escudos en parejas libres."}},
  { id:"s34",label:"Sem. 3 & 4",tema:"Defensa / Contra Ataque",icon:"🛡️",color:"#2d6a4f", warmupGeneral:["Cross Jacks x20","10 sentadillas estáticas + 10 sentadillas con salto","Balística al frente, atrás y a los lados","Flexiones de brazo x10–15","Abdominales x20"], warmupEsp:["Esquives básicos: izquierda, derecha, atrás","Bloqueos 1 al 5 en pareja – respuesta rápida"], basicos:["P.A. Fórmula – Patadas y Ataques","Patadas con esquives integrados","En pareja con paletas – ataque y respuesta defensiva","Con escudos – esquive + contraataque inmediato"], sparring:["Sparring al punto (1 punto = cambio de pareja)","Ronda y sale al punto","Solo manos – bloqueo y contraataque","Solo pies – lectura de distancia y reacción"], anuncios:["Exámenes / Torneos","PANAM"], adapt:{tigers:"Omitir cross jacks y balística. 5 sentadillas simples. Esquive básico: paso atrás.",kids:"Cross jacks y sentadillas x8. Esquive + 1 contraataque. Solo manos supervisado.",adults:"Circuito completo. Esquive + contraataque libre. Sparring al punto y continuo."}},
  { id:"s56",label:"Sem. 5 & 6",tema:"Leer al Oponente",icon:"👁️",color:"#c1440e", warmupGeneral:["Movimientos articulares completos (cuello, hombros, caderas, tobillos)","Rotaciones de cadera en guardia","Movilidad activa: levantamiento recto + circular de pierna"], warmupEsp:["Puños, esquives y bloqueos 1 al 5 – lectura del compañero","Fórmula 4 veces seguidas sin pausa","En parejas con paletas – el que ataca elige el blanco, el otro reacciona"], basicos:["Fórmulas 4 veces en parejas con paletas","Énfasis en lectura del oponente antes de atacar"], sparring:["Combat Weapon – Ángulo 4 y Ángulo 2","Guardia abierta vs cerrada – identificar y explotar","Sparring de reacción – instructor da la señal","Ataques del alumno vs contraataques","Sparring 5 puntos – alta concentración"], anuncios:["Seminarios Grupo 2","PANAM"], adapt:{tigers:"Leer al oponente como juego: ¿de qué lado viene la paleta? Máx. 2 bloqueos.",kids:"Fórmula 2 veces seguidas. Lectura con señal visual (paleta de color).",adults:"Fórmula 4 veces sin pausa. Guardia abierta/cerrada con decisión propia. Sparring 5 puntos."}},
  { id:"s78",label:"Sem. 7 & 8",tema:"Poder y Decisión",icon:"💪",color:"#7b2d8b", warmupGeneral:["Calentamiento completo a criterio del instructor","Énfasis en activación de cadera y core"], warmupEsp:["Puños, esquive y bloqueos 1 al 5 – máxima potencia","Movilidad 4 – secuencia completa de desplazamientos"], basicos:["Segmentos de fórmula con máxima potencia","Armas: Fórmula AR. completa","Énfasis en decisión de técnica según situación"], sparring:["10 sparrings de 2 minutos con diferentes compañeros","20 sparrings de 2 minutos (alta intensidad)","4 series de movilidad táctica entre rondas","8 series de movilidad táctica entre rondas"], anuncios:["Seminarios Grupo 2","PANAM"], adapt:{tigers:"Solo segmentos cortos de fórmula. Sin sparring intenso. Juego de reacción.",kids:"Segmentos de fórmula x potencia. Máx. 4 rondas de 1 minuto.",adults:"Alta exigencia. 10–20 sparrings de 2 min. Movilidad táctica entre rondas."}},
];

const groups = { tigers:{label:"🐯 Tigers (3–6)",accent:"#f7a800",bg:"#fff8e1"}, kids:{label:"🎯 Kids (7–12)",accent:"#28a745",bg:"#e8f5e9"}, adults:{label:"🔱 Adultos (13+)",accent:"#dc3545",bg:"#fdecea"} };
const allLevels = [{key:"beginners",label:"Beginners",color:"#0077b6"},{key:"intermediate",label:"Intermediate",color:"#2d6a4f"},{key:"advanced",label:"Advanced",color:"#c1440e"},{key:"blackbelt",label:"🖤 Black Belt",color:"#1a1a2e"}];

const BBSection = ({title,items,color}) => (
  <div style={{marginBottom:12}}>
    <div style={{background:color,color:"#fff",borderRadius:"6px 6px 0 0",padding:"6px 12px",fontWeight:"bold",fontSize:13}}>{title}</div>
    <div style={{border:`1px solid ${color}`,borderTop:"none",borderRadius:"0 0 6px 6px",padding:"8px 12px",background:"#fff"}}>
      {items.map((item,i)=>(
        <div key={i} style={{display:"flex",gap:8,marginBottom:5,alignItems:"flex-start"}}>
          <span style={{color,fontWeight:"bold",flexShrink:0}}>{i+1}.</span>
          <span style={{fontSize:13,color:"#444",lineHeight:1.5}}>{item}</span>
        </div>
      ))}
    </div>
  </div>
);

// ── APP ───────────────────────────────────────────────────────────────────────
export default function App() {
  const [levelKey,setLevelKey] = useState("beginners");
  const [group,setGroup]       = useState("kids");
  const [bbWeek,setBbWeek]     = useState("s12");
  const [tab,setTab]           = useState("planner");
  const [lecSem,setLecSem]     = useState(lecciones[0].semana);
  const [openCat,setOpenCat]   = useState(null);
  const [selected,setSelected] = useState([]);

  const isBB   = levelKey==="blackbelt";
  const grp    = groups[group];
  const acColor = isBB?"#1a1a2e":levels[levelKey]?.color;

  const toggleEx = (catId,idx) => { const k=`${catId}-${idx}`; setSelected(s=>s.includes(k)?s.filter(x=>x!==k):[...s,k]); };
  const isSel    = (catId,idx) => selected.includes(`${catId}-${idx}`);
  const selExs   = warmupCats.flatMap(c=>c.exercises.map((e,i)=>({...e,catId:c.id,idx:i,color:c.color}))).filter(e=>selected.includes(`${e.catId}-${e.idx}`));

  const tabs = [["planner","📋 Planner"],["warmup","🔥 Calentamiento"],["leccion","📖 Lección de Vida"]];

  return (
    <div style={{fontFamily:"Arial, sans-serif",maxWidth:960,margin:"0 auto",padding:16}}>
      {/* HEADER */}
      <div style={{textAlign:"center",marginBottom:12}}>
        <h1 style={{fontSize:20,fontWeight:"bold",color:"#1a1a2e",margin:"0 0 2px"}}>🥋 Planificador Completo – BEKHO Taekwondo</h1>
        <p style={{fontSize:12,color:"#777",margin:0}}>Todos los niveles · 45 minutos · Adaptado por grupo etario</p>
      </div>

      {/* NIVEL */}
      <div style={{display:"flex",gap:7,justifyContent:"center",marginBottom:8,flexWrap:"wrap"}}>
        {allLevels.map(l=>(
          <button key={l.key} onClick={()=>setLevelKey(l.key)}
            style={{padding:"7px 15px",borderRadius:20,border:"none",cursor:"pointer",fontWeight:"bold",fontSize:13,background:levelKey===l.key?l.color:"#e0e0e0",color:levelKey===l.key?"#fff":"#333"}}>
            {l.label}
          </button>
        ))}
      </div>

      {/* SEMANAS BB */}
      {isBB && (
        <div style={{display:"flex",gap:7,justifyContent:"center",marginBottom:8,flexWrap:"wrap"}}>
          {bbWeeks.map(w=>(
            <button key={w.id} onClick={()=>setBbWeek(w.id)}
              style={{padding:"6px 13px",borderRadius:20,border:"none",cursor:"pointer",fontWeight:"bold",fontSize:12,background:bbWeek===w.id?w.color:"#e0e0e0",color:bbWeek===w.id?"#fff":"#333"}}>
              {w.icon} {w.label}
            </button>
          ))}
        </div>
      )}

      {/* GRUPO */}
      <div style={{display:"flex",gap:7,justifyContent:"center",marginBottom:12,flexWrap:"wrap"}}>
        {Object.entries(groups).map(([k,v])=>(
          <button key={k} onClick={()=>setGroup(k)}
            style={{padding:"6px 15px",borderRadius:20,border:"none",cursor:"pointer",fontWeight:"bold",fontSize:12,background:group===k?v.accent:"#e0e0e0",color:group===k?"#fff":"#333"}}>
            {v.label}
          </button>
        ))}
      </div>

      {/* TABS */}
      <div style={{display:"flex",gap:0,marginBottom:16,borderRadius:10,overflow:"hidden",border:`2px solid ${acColor}`}}>
        {tabs.map(([key,lbl])=>(
          <button key={key} onClick={()=>setTab(key)}
            style={{flex:1,padding:"10px",border:"none",cursor:"pointer",fontWeight:"bold",fontSize:13,background:tab===key?acColor:"#f0f0f0",color:tab===key?"#fff":"#555"}}>
            {lbl}
          </button>
        ))}
      </div>

      {/* ══ TAB: PLANNER REGULAR ══ */}
      {tab==="planner" && !isBB && (()=>{
        const lvl=levels[levelKey]; const sch=buildSchedule(lvl,group);
        return (
          <>
            <div style={{background:grp.bg,border:`2px solid ${grp.accent}`,borderRadius:12,padding:"10px 14px",marginBottom:14,display:"flex",justifyContent:"space-between",alignItems:"center",flexWrap:"wrap",gap:8}}>
              <div>
                <span style={{fontWeight:"bold",color:lvl.color,fontSize:14}}>Nivel: {lvl.label}</span>
                <span style={{margin:"0 8px",color:"#ccc"}}>|</span>
                <span style={{fontWeight:"bold",color:grp.accent,fontSize:14}}>{grp.label}</span>
                <span style={{margin:"0 8px",color:"#ccc"}}>|</span>
                <span style={{fontSize:12,color:"#666"}}>Defensa: {lvl.defensa}</span>
              </div>
              <span style={{fontSize:12,background:"#fff",borderRadius:8,padding:"4px 12px",fontWeight:"bold"}}>⏱ 45 min</span>
            </div>

            {/* Combinaciones de patadas */}
            <div style={{background:"#fff",border:`1.5px solid ${lvl.color}`,borderRadius:10,padding:"10px 14px",marginBottom:14}}>
              <p style={{margin:"0 0 8px",fontWeight:"bold",fontSize:13,color:lvl.color}}>🦵 Combinaciones de Patadas – {lvl.label}</p>
              <div style={{display:"flex",gap:8,flexWrap:"wrap"}}>
                {lvl.combinaciones.map((c,i)=>(
                  <span key={i} style={{background:`${lvl.color}15`,border:`1px solid ${lvl.color}`,borderRadius:8,padding:"5px 10px",fontSize:12,fontWeight:"bold",color:lvl.color}}>
                    {i+1}. {c}
                  </span>
                ))}
              </div>
            </div>

            <div style={{overflowX:"auto"}}>
              <table style={{width:"100%",borderCollapse:"collapse",fontSize:13}}>
                <thead>
                  <tr style={{background:lvl.color,color:"#fff"}}>
                    <th style={{padding:"9px 8px",textAlign:"left",width:80,whiteSpace:"nowrap"}}>⏱ Tiempo</th>
                    <th style={{padding:"9px 8px",textAlign:"left",width:175}}>Bloque</th>
                    <th style={{padding:"9px 8px",textAlign:"left"}}>Actividad y detalle</th>
                    <th style={{padding:"9px 8px",textAlign:"left",width:130}}>Cuadrante</th>
                  </tr>
                </thead>
                <tbody>
                  {sch.map((row,i)=>(
                    <tr key={i} style={{background:i%2===0?"#f9f9f9":"#fff",verticalAlign:"top"}}>
                      <td style={{padding:"9px 8px",fontWeight:"bold",color:lvl.color,whiteSpace:"nowrap"}}>{row.time}</td>
                      <td style={{padding:"9px 8px",fontWeight:"bold",color:"#222"}}>{row.block}</td>
                      <td style={{padding:"9px 8px",color:"#444",lineHeight:1.55,whiteSpace:"pre-line"}}>{row.detail}</td>
                      <td style={{padding:"9px 8px"}}>
                        <span style={{background:cuadColor[row.cuadrante]||"#999",color:"#fff",borderRadius:6,padding:"3px 7px",fontSize:11,fontWeight:"bold",display:"inline-block",lineHeight:1.5}}>{row.cuadrante}</span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div style={{marginTop:14,background:"#f4f4f4",borderRadius:12,padding:14}}>
              <p style={{margin:"0 0 10px",fontWeight:"bold",fontSize:13,color:"#333"}}>📋 Diferencias de programa – {lvl.label}</p>
              <div style={{display:"flex",gap:10,flexWrap:"wrap"}}>
                {[
                  {title:levelKey==="beginners"?"Alumno Taekwondo":"Alumno Regular",items:["Fórmula tradicional hasta la mitad","Fórmula de armas simple","ATA MAX (Xtreme) Forms y Weapons"]},
                  {title:levelKey==="beginners"?"Alumno TKD Leadership":"Alumno TKD Leadership",items:["Fórmula tradicional completa","Fórmula de armas simple y dobles","ATA MAX (Xtreme) Forms y Weapons"]},
                ].map((col,i)=>(
                  <div key={i} style={{flex:1,minWidth:180,background:"#fff",borderRadius:8,padding:"10px 12px",border:`1px solid ${lvl.color}`}}>
                    <p style={{fontWeight:"bold",color:lvl.color,margin:"0 0 6px",fontSize:12}}>{col.title}</p>
                    <ul style={{margin:0,paddingLeft:16,fontSize:12,color:"#444",lineHeight:1.9}}>
                      {col.items.map((it,j)=><li key={j}>{it}</li>)}
                    </ul>
                  </div>
                ))}
              </div>
            </div>
          </>
        );
      })()}

      {/* ══ TAB: PLANNER BB ══ */}
      {tab==="planner" && isBB && (()=>{
        const w=bbWeeks.find(x=>x.id===bbWeek);
        return (
          <>
            <div style={{background:w.color,color:"#fff",borderRadius:12,padding:"12px 18px",marginBottom:12,display:"flex",justifyContent:"space-between",alignItems:"center",flexWrap:"wrap",gap:8}}>
              <div><span style={{fontSize:20,marginRight:8}}>{w.icon}</span><span style={{fontWeight:"bold",fontSize:16}}>{w.label}</span><span style={{marginLeft:10,fontSize:13,opacity:0.85}}>Tema: {w.tema}</span></div>
              <span style={{background:"#fff",color:w.color,borderRadius:8,padding:"4px 12px",fontWeight:"bold",fontSize:12}}>⏱ 45 min · {grp.label}</span>
            </div>
            <div style={{background:`${grp.accent}18`,border:`1.5px solid ${grp.accent}`,borderRadius:10,padding:"8px 14px",marginBottom:14,fontSize:13}}>
              <strong style={{color:grp.accent}}>💡 Adaptación {grp.label}:</strong>
              <span style={{color:"#444",marginLeft:6}}>{w.adapt[group]}</span>
            </div>
            <div style={{display:"flex",gap:14,flexWrap:"wrap"}}>
              <div style={{flex:1,minWidth:260}}>
                <BBSection title="🔥 Warm Up General"              items={w.warmupGeneral} color={w.color}/>
                <BBSection title="🎯 Warm Up Específico"           items={w.warmupEsp}     color={w.color}/>
                <BBSection title="📋 Anuncios"                     items={w.anuncios}      color="#6c757d"/>
              </div>
              <div style={{flex:1,minWidth:260}}>
                <BBSection title="⭐ Básicos / Fórmulas / Patadas" items={w.basicos}       color={w.color}/>
                <BBSection title="⚔️ Sparring / Combat Weapon"    items={w.sparring}      color={w.color}/>
              </div>
            </div>
          </>
        );
      })()}

      {/* ══ TAB: CALENTAMIENTO ══ */}
      {tab==="warmup" && (()=>{
        const allowed=warmupFilters[group];
        const visCats=warmupCats.filter(c=>allowed.includes(c.id));
        return (
          <>
            <div style={{background:grp.bg,border:`1.5px solid ${grp.accent}`,borderRadius:10,padding:"8px 14px",marginBottom:14,fontSize:13}}>
              <strong style={{color:grp.accent}}>💡 Nota para {grp.label}:</strong>
              <span style={{color:"#444",marginLeft:6}}>{warmupNotes[group]}</span>
            </div>
            <div style={{display:"flex",gap:16,flexWrap:"wrap",alignItems:"flex-start"}}>
              <div style={{flex:2,minWidth:280}}>
                {visCats.map(cat=>(
                  <div key={cat.id} style={{marginBottom:10,border:`2px solid ${cat.color}`,borderRadius:10,overflow:"hidden"}}>
                    <div onClick={()=>setOpenCat(openCat===cat.id?null:cat.id)}
                      style={{background:cat.color,color:"#fff",padding:"10px 14px",cursor:"pointer",display:"flex",justifyContent:"space-between",alignItems:"center",fontWeight:"bold",fontSize:14}}>
                      <span>{cat.label}</span><span style={{fontSize:18}}>{openCat===cat.id?"▲":"▼"}</span>
                    </div>
                    {openCat===cat.id && cat.exercises.map((ex,i)=>(
                      <div key={i} onClick={()=>toggleEx(cat.id,i)}
                        style={{padding:"10px 14px",borderBottom:"1px solid #eee",cursor:"pointer",background:isSel(cat.id,i)?`${cat.color}18`:"#fff",display:"flex",gap:10,alignItems:"flex-start"}}>
                        <span style={{fontSize:18,marginTop:1,flexShrink:0}}>{isSel(cat.id,i)?"✅":"⬜"}</span>
                        <div>
                          <p style={{margin:"0 0 3px",fontWeight:"bold",fontSize:13,color:cat.color}}>{ex.name}</p>
                          <p style={{margin:0,fontSize:12,color:"#555",lineHeight:1.5}}>{ex.desc}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                ))}
              </div>
              <div style={{flex:1,minWidth:230}}>
                <div style={{background:acColor,color:"#fff",borderRadius:"10px 10px 0 0",padding:"10px 14px",display:"flex",justifyContent:"space-between",alignItems:"center"}}>
                  <span style={{fontWeight:"bold",fontSize:14}}>📋 Mi Rutina ({selExs.length})</span>
                  {selExs.length>0 && <button onClick={()=>setSelected([])} style={{background:"#c1440e",color:"#fff",border:"none",borderRadius:6,padding:"3px 10px",cursor:"pointer",fontSize:11}}>Limpiar</button>}
                </div>
                <div style={{border:`2px solid ${acColor}`,borderTop:"none",borderRadius:"0 0 10px 10px",minHeight:120,padding:10,background:"#f9f9f9"}}>
                  {selExs.length===0
                    ? <p style={{color:"#aaa",fontSize:12,textAlign:"center",marginTop:30}}>Toca un ejercicio para agregarlo aquí</p>
                    : selExs.map((ex,i)=>(
                      <div key={i} style={{display:"flex",alignItems:"flex-start",gap:8,marginBottom:8,background:"#fff",borderRadius:8,padding:"7px 10px",border:`1px solid ${ex.color}`}}>
                        <span style={{background:ex.color,color:"#fff",borderRadius:"50%",width:20,height:20,display:"flex",alignItems:"center",justifyContent:"center",fontSize:11,fontWeight:"bold",flexShrink:0}}>{i+1}</span>
                        <div>
                          <p style={{margin:0,fontWeight:"bold",fontSize:12,color:ex.color}}>{ex.name}</p>
                          <p style={{margin:"2px 0 0",fontSize:11,color:"#666",lineHeight:1.4}}>{ex.desc}</p>
                        </div>
                      </div>
                    ))
                  }
                  {selExs.length>0 && <div style={{marginTop:10,background:"#e8f5e9",borderRadius:8,padding:"7px 10px",fontSize:12,color:"#2d6a4f",fontWeight:"bold"}}>⏱ Tiempo estimado: {selExs.length*2}–{selExs.length*3} min</div>}
                </div>
              </div>
            </div>
          </>
        );
      })()}

      {/* ══ TAB: LECCIÓN DE VIDA ══ */}
      {tab==="leccion" && (()=>{
        const lec=lecciones.find(l=>l.semana===lecSem)||lecciones[0];
        const momentos=[
          {key:"comienzo",label:"🟢 Comienzo de la Clase",color:"#2d6a4f",data:lec.comienzo},
          {key:"durante", label:"🟡 Durante la Clase",    color:"#f77f00",data:lec.durante},
          {key:"fin",     label:"🔴 Fin de la Clase",     color:"#c1440e",data:lec.fin},
        ];
        return (
          <>
            {/* Selector de semana */}
            <div style={{display:"flex",gap:8,justifyContent:"center",marginBottom:14,flexWrap:"wrap"}}>
              {lecciones.map(l=>(
                <button key={l.semana} onClick={()=>setLecSem(l.semana)}
                  style={{padding:"7px 18px",borderRadius:20,border:"none",cursor:"pointer",fontWeight:"bold",fontSize:13,background:lecSem===l.semana?l.color:"#e0e0e0",color:lecSem===l.semana?"#fff":"#333"}}>
                  Semana {l.semana}
                </button>
              ))}
            </div>

            {/* Header lección */}
            <div style={{background:"#1a1a2e",color:"#fff",borderRadius:12,padding:"14px 20px",marginBottom:16,textAlign:"center"}}>
              <p style={{margin:"0 0 4px",fontSize:11,letterSpacing:2,opacity:0.7,textTransform:"uppercase"}}>Lección de Vida · ATA Legacy</p>
              <h2 style={{margin:"0 0 4px",fontSize:22}}>📖 {lec.tema}</h2>
              <p style={{margin:0,fontSize:13,opacity:0.8}}>Semana {lec.semana}</p>
            </div>

            {/* Momentos */}
            <div style={{display:"flex",flexDirection:"column",gap:12}}>
              {momentos.map(m=>(
                <div key={m.key} style={{border:`2px solid ${m.color}`,borderRadius:12,overflow:"hidden"}}>
                  <div style={{background:m.color,color:"#fff",padding:"10px 16px",fontWeight:"bold",fontSize:14}}>{m.label}</div>
                  <div style={{padding:"14px 16px",background:"#fff"}}>
                    <p style={{margin:"0 0 10px",fontSize:14,color:"#333",lineHeight:1.7}}>{m.data.texto}</p>
                    <div style={{background:`${m.color}15`,border:`1.5px solid ${m.color}`,borderRadius:8,padding:"10px 16px",textAlign:"center"}}>
                      <p style={{margin:0,fontWeight:"bold",fontSize:15,color:m.color,letterSpacing:0.5}}>"{m.data.frase}"</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div style={{marginTop:14,background:"#f4f4f4",borderRadius:10,padding:"10px 14px",fontSize:12,color:"#666",textAlign:"center"}}>
              💡 La Lección de Vida se presenta en 3 momentos clave de la clase. Adapta el lenguaje según el grupo etario.
            </div>
          </>
        );
      })()}

      <p style={{textAlign:"center",fontSize:11,color:"#ccc",marginTop:16}}>BEKHO Taekwondo · Planificador Completo · Ciclo actualizado</p>
    </div>
  );
}
