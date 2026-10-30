const fs = require('fs');
let css = fs.readFileSync('assets/css/enhancements.css', 'utf8');

css += `
/* Horizontal Workflow Pipeline */
.workflow-pipeline {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding: 20px 5px;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}
.pipeline-node {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    min-width: max-content;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}
.pipeline-node:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-color: #94a3b8;
}
.node-badge {
    background: #0f172a;
    color: #fff;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}
.node-content {
    display: flex;
    flex-direction: column;
}
.node-content strong { color: #1e293b; font-size: 14px; }
.node-content small { color: #64748b; font-size: 12px; }
.node-actions {
    margin-left: 10px;
}
.node-delete {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 14px;
    opacity: 0.5;
    transition: opacity 0.2s;
}
.node-delete:hover { opacity: 1; }
.pipeline-connector {
    color: #94a3b8;
    font-size: 18px;
}
`;

fs.writeFileSync('assets/css/enhancements.css', css, 'utf8');
console.log('Appended CSS');

