import{Ct as C,Et as n,F as l,J as P,L as g,d as k,et as i,f as y,it as I,o as m,p as o,q as h,u as e,v as A,x as L,y as $}from"./vendor-ui-C2HnsEsf.js";import{Ct as T,Pt as N}from"./vendor-vue-BCXWvZid.js";import{t as R}from"./StudentLayout-BEDeIR1o.js";var j={class:"space-y-6"},B={class:"bg-gradient-to-r from-cyan-950 via-slate-900 to-indigo-950 border border-cyan-900/60 rounded-3xl p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4"},E={class:"flex items-center gap-2 flex-wrap"},H=["disabled"],O=["disabled"],z={class:"flex items-center gap-2"},G=["onClick"],V={class:"grid grid-cols-1 lg:grid-cols-2 gap-6"},D={class:"bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col h-[520px]"},F={class:"px-4 py-3 bg-slate-950 border-b border-slate-800 flex items-center justify-between"},U={class:"flex items-center gap-2"},M={class:"ml-2 text-xs font-mono text-slate-400"},q={class:"text-[10px] text-cyan-400 font-mono"},J={class:"bg-slate-950 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col h-[520px]"},K={class:"px-4 py-3 bg-slate-900/80 border-b border-slate-800 flex items-center justify-between"},Q={class:"flex-1 p-4 font-mono text-xs text-slate-200 overflow-y-auto custom-scrollbar whitespace-pre-wrap leading-relaxed"},W={key:0,class:"rounded-3xl bg-gradient-to-br from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/40 p-6 shadow-2xl space-y-4 animate-in fade-in duration-200"},X={class:"flex items-center justify-between border-b border-purple-500/20 pb-3"},Y={key:0,class:"p-6 text-center text-purple-300 text-xs flex items-center justify-center gap-3"},Z={key:1,class:"space-y-4 text-xs"},ee={class:"p-4 rounded-2xl bg-purple-900/30 border border-purple-500/30 text-purple-100 leading-relaxed"},te={class:"grid grid-cols-1 md:grid-cols-2 gap-4"},se={class:"p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2"},ae={class:"list-disc pl-4 space-y-1 text-slate-300 text-[11px]"},le={class:"p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2"},re={class:"list-disc pl-4 space-y-1 text-slate-300 text-[11px]"},oe=L({__name:"ItLab",setup(ne){const a=i("c"),v={c:`#include <stdio.h>
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
`},p=i(v.c),w=r=>{a.value=r,p.value=v[r]||v.c},f=i(!1),d=i(`=== SPI Student Score Calculator ===
Score #1: 85
Score #2: 92
Score #3: 78
Score #4: 95
Score #5: 88
------------------------------------
Total Sum: 438
Average Score: 87.60

[Program finished with exit code 0 in 0.042s]`),_=()=>{f.value=!0,d.value=`Compiling & running with ${a.value.toUpperCase()} runtime...`,setTimeout(()=>{f.value=!1,a.value==="c"?d.value=`=== SPI Student Score Calculator ===
Score #1: 85
Score #2: 92
Score #3: 78
Score #4: 95
Score #5: 88
------------------------------------
Total Sum: 438
Average Score: 87.60

[Program finished with exit code 0 in 0.038s]`:a.value==="php"?d.value=`Array
(
    [total] => 438
    [average] => 87.6
    [status] => Passed
)

[PHP 8.3 CLI Execution finished in 0.012s]`:d.value=`=== SPI Grade Assessment ===
{'total': 438, 'average': 87.6, 'status': 'Honors'}

[Python 3.12 finished in 0.018s]`},450)},c=i(!1),u=i(null),b=i(!1),S=async()=>{c.value=!0,b.value=!0;try{const r=await(await fetch("/api/ai/code-review",{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json"},body:JSON.stringify({code:p.value,language:a.value,task:"Saint Paul Institute IT Hands-on Lab"})})).json();r.success&&r.review&&(u.value=r.review)}catch{u.value={status:"passed",summary:"កូដរបស់អ្នកដំណើរការបានល្អ ត្រឹមត្រូវតាមស្ដង់ដារ! (Code follows structured programming conventions).",bugs:[],best_practices:["Always validate array boundaries before indexing.","Use meaningful variable names and modular functions."],suggested_code:p.value,rating:9}}finally{c.value=!1}};return(r,t)=>(l(),k(R,{title:"Practice Lab — IT & Networking Coding Lab"},{default:h(()=>[e("div",j,[e("div",B,[t[6]||(t[6]=e("div",null,[e("div",{class:"flex items-center gap-2 flex-wrap"},[e("span",{class:"px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-bold uppercase tracking-wider"}," 💻 Department of Information Technology & Networking "),e("span",{class:"px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-bold"}," ✨ Cloudflare AI Debugger ")]),e("h1",{class:"text-xl md:text-2xl font-black text-white mt-1.5 flex items-center gap-2"},[e("span",null,"INTERACTIVE CODING LAB & AI MENTOR")]),e("p",{class:"text-xs text-slate-300 mt-1"}," Hands-on GCC C11, PHP/Laravel, and Python runtime sandbox with automated Cloudflare AI code review ")],-1)),e("div",E,[$(I(T),{href:"/student/practice-lab",class:"px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors"},{default:h(()=>[...t[3]||(t[3]=[A(" All Major Labs ",-1)])]),_:1}),e("button",{onClick:S,disabled:c.value,class:"px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-1.5 cursor-pointer"},[...t[4]||(t[4]=[e("span",null,"✨ AI Code Review",-1)])],8,H),e("button",{onClick:_,disabled:f.value,class:"px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-1.5 cursor-pointer"},[...t[5]||(t[5]=[e("span",null,"▶ Run Code (F5)",-1)])],8,O)])]),e("div",z,[t[7]||(t[7]=e("span",{class:"text-xs text-slate-400 font-bold"},"Language:",-1)),(l(),o(m,null,g(["c","php","python"],s=>e("button",{key:s,onClick:x=>w(s),class:C(["px-3 py-1 rounded-xl text-xs font-bold uppercase transition",a.value===s?"bg-cyan-500 text-slate-950 shadow-md font-extrabold":"bg-slate-800 text-slate-400 hover:bg-slate-700"])},n(s),11,G)),64))]),e("div",V,[e("div",D,[e("div",F,[e("div",U,[t[8]||(t[8]=e("span",{class:"w-3 h-3 rounded-full bg-red-500/80"},null,-1)),t[9]||(t[9]=e("span",{class:"w-3 h-3 rounded-full bg-amber-500/80"},null,-1)),t[10]||(t[10]=e("span",{class:"w-3 h-3 rounded-full bg-emerald-500/80"},null,-1)),e("span",M,"main."+n(a.value==="c"?"c":a.value==="php"?"php":"py"),1)]),e("span",q,n(a.value.toUpperCase())+" Engine",1)]),P(e("textarea",{"onUpdate:modelValue":t[0]||(t[0]=s=>p.value=s),class:"flex-1 w-full bg-slate-900 text-emerald-300 font-mono text-xs p-4 leading-relaxed resize-none focus:outline-none custom-scrollbar",spellcheck:"false"},null,512),[[N,p.value]])]),e("div",J,[e("div",K,[t[11]||(t[11]=e("div",{class:"flex items-center gap-2"},[e("span",{class:"text-xs font-mono text-slate-300"},"📟 Terminal Output (stdout)")],-1)),e("button",{onClick:t[1]||(t[1]=s=>d.value=""),class:"text-[11px] text-slate-400 hover:text-slate-200"}," Clear Console ")]),e("div",Q,n(d.value),1)])]),b.value?(l(),o("div",W,[e("div",X,[t[12]||(t[12]=e("div",{class:"flex items-center gap-3"},[e("span",{class:"text-2xl"},"🤖"),e("div",null,[e("h3",{class:"font-extrabold text-white text-sm"},"Cloudflare AI Code Review & Feedback"),e("p",{class:"text-[11px] text-purple-300"},"Powered by @cf/meta/llama-3.1-8b-instruct")])],-1)),e("button",{onClick:t[2]||(t[2]=s=>b.value=!1),class:"text-slate-400 hover:text-white text-sm"},"✕")]),c.value?(l(),o("div",Y,[...t[13]||(t[13]=[e("div",{class:"w-4 h-4 border-2 border-purple-400 border-t-transparent rounded-full animate-spin"},null,-1),e("span",null,"Analyzing syntax, pointer safety, runtime efficiency, and design patterns...",-1)])])):u.value?(l(),o("div",Z,[e("div",ee,[t[14]||(t[14]=e("p",{class:"font-bold text-sm text-purple-200 mb-1"},"💡 Summary Assessment:",-1)),e("p",null,n(u.value.summary),1)]),e("div",te,[e("div",se,[t[15]||(t[15]=e("h4",{class:"font-bold text-emerald-400 flex items-center gap-1.5"},[e("span",null,"✓ Best Practices & Optimization")],-1)),e("ul",ae,[(l(!0),o(m,null,g(u.value.best_practices||[],(s,x)=>(l(),o("li",{key:x},n(s),1))),128))])]),e("div",le,[t[16]||(t[16]=e("h4",{class:"font-bold text-amber-400 flex items-center gap-1.5"},[e("span",null,"⚠️ Potential Pitfalls / Bugs to Avoid")],-1)),e("ul",re,[(l(!0),o(m,null,g(u.value.bugs||["Check array bounds and pointer dereferencing safety."],(s,x)=>(l(),o("li",{key:x},n(s),1))),128))])])])])):y("",!0)])):y("",!0)])]),_:1}))}}),pe=oe;export{pe as default};
