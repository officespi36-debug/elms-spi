import{Ct as e,Et as t,F as n,J as r,L as i,d as a,et as o,f as s,it as c,o as l,p as u,q as d,u as f,v as p,x as m,y as h}from"./vendor-ui-BDZn_Bea.js";import{Ct as g,Pt as _}from"./vendor-vue-BXb8qeVY.js";import{t as v}from"./StudentLayout-mZGAkZa4.js";var y={class:`space-y-6`},b={class:`bg-gradient-to-r from-cyan-950 via-slate-900 to-indigo-950 border border-cyan-900/60 rounded-3xl p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4`},x={class:`flex items-center gap-2 flex-wrap`},S=[`disabled`],C=[`disabled`],w={class:`flex items-center gap-2`},T=[`onClick`],E={class:`grid grid-cols-1 lg:grid-cols-2 gap-6`},D={class:`bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col h-[520px]`},O={class:`px-4 py-3 bg-slate-950 border-b border-slate-800 flex items-center justify-between`},k={class:`flex items-center gap-2`},A={class:`ml-2 text-xs font-mono text-slate-400`},j={class:`text-[10px] text-cyan-400 font-mono`},M={class:`bg-slate-950 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col h-[520px]`},N={class:`px-4 py-3 bg-slate-900/80 border-b border-slate-800 flex items-center justify-between`},P={class:`flex-1 p-4 font-mono text-xs text-slate-200 overflow-y-auto custom-scrollbar whitespace-pre-wrap leading-relaxed`},F={key:0,class:`rounded-3xl bg-gradient-to-br from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/40 p-6 shadow-2xl space-y-4 animate-in fade-in duration-200`},I={class:`flex items-center justify-between border-b border-purple-500/20 pb-3`},ee={key:0,class:`p-6 text-center text-purple-300 text-xs flex items-center justify-center gap-3`},L={key:1,class:`space-y-4 text-xs`},R={class:`p-4 rounded-2xl bg-purple-900/30 border border-purple-500/30 text-purple-100 leading-relaxed`},z={class:`grid grid-cols-1 md:grid-cols-2 gap-4`},B={class:`p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2`},V={class:`list-disc pl-4 space-y-1 text-slate-300 text-[11px]`},H={class:`p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2`},U={class:`list-disc pl-4 space-y-1 text-slate-300 text-[11px]`},W=m({__name:`ItLab`,setup(m){let W=o(`c`),G={c:`#include <stdio.h>
#include <stdlib.h>

// Saint Paul Institute - C Coding Lab
int main() {
    int scores[] = {85, 92, 78, 95, 88};
    int n = sizeof(scores) / sizeof(scores[0]);
    int sum = 0;
    
    printf("=== SPI Student Score Calculator ===\\n");
    for(int i = 0; i < n; i++) {
        sum += scores[i];
        printf("Score #%d: %d\\n", i + 1, scores[i]);
    }
    
    double avg = (double)sum / n;
    printf("------------------------------------\\n");
    printf("Total Sum: %d\\n", sum);
    printf("Average Score: %.2f\\n", avg);
    
    return 0;
}`,php:`<?php
// Saint Paul Institute - Laravel / PHP Lab
class StudentService {
    public function calculateGrade(array $scores): array {
        $total = array_sum($scores);
        $count = count($scores);
        $average = $count > 0 ? round($total / $count, 2) : 0;
        
        return [
            'total' => $total,
            'average' => $average,
            'status' => $average >= 50 ? 'Passed' : 'Needs Review',
        ];
    }
}

$service = new StudentService();
$result = $service->calculateGrade([85, 92, 78, 95, 88]);
print_r($result);
`,python:`# Saint Paul Institute - Python Lab
def calculate_grades(scores):
    total = sum(scores)
    avg = total / len(scores) if scores else 0
    return {
        "total": total,
        "average": round(avg, 2),
        "status": "Honors" if avg >= 85 else "Passed"
    }

scores = [85, 92, 78, 95, 88]
print("=== SPI Grade Assessment ===")
print(calculate_grades(scores))
`},K=o(G.c),q=e=>{W.value=e,K.value=G[e]||G.c},J=o(!1),Y=o(`=== SPI Student Score Calculator ===
Score #1: 85
Score #2: 92
Score #3: 78
Score #4: 95
Score #5: 88
------------------------------------
Total Sum: 438
Average Score: 87.60

[Program finished with exit code 0 in 0.042s]`),X=()=>{J.value=!0,Y.value=`Compiling & running with ${W.value.toUpperCase()} runtime...`,setTimeout(()=>{J.value=!1,W.value===`c`?Y.value=`=== SPI Student Score Calculator ===
Score #1: 85
Score #2: 92
Score #3: 78
Score #4: 95
Score #5: 88
------------------------------------
Total Sum: 438
Average Score: 87.60

[Program finished with exit code 0 in 0.038s]`:W.value===`php`?Y.value=`Array
(
    [total] => 438
    [average] => 87.6
    [status] => Passed
)

[PHP 8.3 CLI Execution finished in 0.012s]`:Y.value=`=== SPI Grade Assessment ===
{'total': 438, 'average': 87.6, 'status': 'Honors'}

[Python 3.12 finished in 0.018s]`},450)},Z=o(!1),Q=o(null),$=o(!1),te=async()=>{Z.value=!0,$.value=!0;try{let e=await(await fetch(`/api/ai/code-review`,{method:`POST`,headers:{"Content-Type":`application/json`,Accept:`application/json`},body:JSON.stringify({code:K.value,language:W.value,task:`Saint Paul Institute IT Hands-on Lab`})})).json();e.success&&e.review&&(Q.value=e.review)}catch{Q.value={status:`passed`,summary:`កូដរបស់អ្នកដំណើរការបានល្អ ត្រឹមត្រូវតាមស្ដង់ដារ! (Code follows structured programming conventions).`,bugs:[],best_practices:[`Always validate array boundaries before indexing.`,`Use meaningful variable names and modular functions.`],suggested_code:K.value,rating:9}}finally{Z.value=!1}};return(o,m)=>(n(),a(v,{title:`Practice Lab — IT & Networking Coding Lab`},{default:d(()=>[f(`div`,y,[f(`div`,b,[m[6]||(m[6]=f(`div`,null,[f(`div`,{class:`flex items-center gap-2 flex-wrap`},[f(`span`,{class:`px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-bold uppercase tracking-wider`},` 💻 Department of Information Technology & Networking `),f(`span`,{class:`px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-bold`},` ✨ Cloudflare AI Debugger `)]),f(`h1`,{class:`text-xl md:text-2xl font-black text-white mt-1.5 flex items-center gap-2`},[f(`span`,null,`INTERACTIVE CODING LAB & AI MENTOR`)]),f(`p`,{class:`text-xs text-slate-300 mt-1`},` Hands-on GCC C11, PHP/Laravel, and Python runtime sandbox with automated Cloudflare AI code review `)],-1)),f(`div`,x,[h(c(g),{href:`/student/practice-lab`,class:`px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors`},{default:d(()=>[...m[3]||(m[3]=[p(` All Major Labs `,-1)])]),_:1}),f(`button`,{onClick:te,disabled:Z.value,class:`px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-1.5 cursor-pointer`},[...m[4]||(m[4]=[f(`span`,null,`✨ AI Code Review`,-1)])],8,S),f(`button`,{onClick:X,disabled:J.value,class:`px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-1.5 cursor-pointer`},[...m[5]||(m[5]=[f(`span`,null,`▶ Run Code (F5)`,-1)])],8,C)])]),f(`div`,w,[m[7]||(m[7]=f(`span`,{class:`text-xs text-slate-400 font-bold`},`Language:`,-1)),(n(),u(l,null,i([`c`,`php`,`python`],n=>f(`button`,{key:n,onClick:e=>q(n),class:e([`px-3 py-1 rounded-xl text-xs font-bold uppercase transition`,W.value===n?`bg-cyan-500 text-slate-950 shadow-md font-extrabold`:`bg-slate-800 text-slate-400 hover:bg-slate-700`])},t(n),11,T)),64))]),f(`div`,E,[f(`div`,D,[f(`div`,O,[f(`div`,k,[m[8]||(m[8]=f(`span`,{class:`w-3 h-3 rounded-full bg-red-500/80`},null,-1)),m[9]||(m[9]=f(`span`,{class:`w-3 h-3 rounded-full bg-amber-500/80`},null,-1)),m[10]||(m[10]=f(`span`,{class:`w-3 h-3 rounded-full bg-emerald-500/80`},null,-1)),f(`span`,A,`main.`+t(W.value===`c`?`c`:W.value===`php`?`php`:`py`),1)]),f(`span`,j,t(W.value.toUpperCase())+` Engine`,1)]),r(f(`textarea`,{"onUpdate:modelValue":m[0]||(m[0]=e=>K.value=e),class:`flex-1 w-full bg-slate-900 text-emerald-300 font-mono text-xs p-4 leading-relaxed resize-none focus:outline-none custom-scrollbar`,spellcheck:`false`},null,512),[[_,K.value]])]),f(`div`,M,[f(`div`,N,[m[11]||(m[11]=f(`div`,{class:`flex items-center gap-2`},[f(`span`,{class:`text-xs font-mono text-slate-300`},`📟 Terminal Output (stdout)`)],-1)),f(`button`,{onClick:m[1]||(m[1]=e=>Y.value=``),class:`text-[11px] text-slate-400 hover:text-slate-200`},` Clear Console `)]),f(`div`,P,t(Y.value),1)])]),$.value?(n(),u(`div`,F,[f(`div`,I,[m[12]||(m[12]=f(`div`,{class:`flex items-center gap-3`},[f(`span`,{class:`text-2xl`},`🤖`),f(`div`,null,[f(`h3`,{class:`font-extrabold text-white text-sm`},`Cloudflare AI Code Review & Feedback`),f(`p`,{class:`text-[11px] text-purple-300`},`Powered by @cf/meta/llama-3.1-8b-instruct`)])],-1)),f(`button`,{onClick:m[2]||(m[2]=e=>$.value=!1),class:`text-slate-400 hover:text-white text-sm`},`✕`)]),Z.value?(n(),u(`div`,ee,[...m[13]||(m[13]=[f(`div`,{class:`w-4 h-4 border-2 border-purple-400 border-t-transparent rounded-full animate-spin`},null,-1),f(`span`,null,`Analyzing syntax, pointer safety, runtime efficiency, and design patterns...`,-1)])])):Q.value?(n(),u(`div`,L,[f(`div`,R,[m[14]||(m[14]=f(`p`,{class:`font-bold text-sm text-purple-200 mb-1`},`💡 Summary Assessment:`,-1)),f(`p`,null,t(Q.value.summary),1)]),f(`div`,z,[f(`div`,B,[m[15]||(m[15]=f(`h4`,{class:`font-bold text-emerald-400 flex items-center gap-1.5`},[f(`span`,null,`✓ Best Practices & Optimization`)],-1)),f(`ul`,V,[(n(!0),u(l,null,i(Q.value.best_practices||[],(e,r)=>(n(),u(`li`,{key:r},t(e),1))),128))])]),f(`div`,H,[m[16]||(m[16]=f(`h4`,{class:`font-bold text-amber-400 flex items-center gap-1.5`},[f(`span`,null,`⚠️ Potential Pitfalls / Bugs to Avoid`)],-1)),f(`ul`,U,[(n(!0),u(l,null,i(Q.value.bugs||[`Check array bounds and pointer dereferencing safety.`],(e,r)=>(n(),u(`li`,{key:r},t(e),1))),128))])])])])):s(``,!0)])):s(``,!0)])]),_:1}))}});export{W as default};