// PDF
document.getElementById('btnPDF').addEventListener('click', function() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(16);
    doc.text("Reporte de Asistencia", 20, 20);

    // Cabecera
    const headers = [["Estudiante", "Fecha", "Estado"]];
    
    // Datos de la tabla
    const rows = [];
    document.querySelectorAll("#tablaReporte tr").forEach((tr, index) => {
        if(index === 0) return; // saltar encabezado
        const row = [];
        tr.querySelectorAll("td").forEach(td => row.push(td.innerText));
        rows.push(row);
    });

    doc.autoTable({
        head: headers,
        body: rows,
        startY: 30
    });

    doc.save("Reporte_Asistencia.pdf");
});

// Excel
document.getElementById('btnExcel').addEventListener('click', function() {
    const wb = XLSX.utils.book_new();
    const ws_data = [];

    // Cabecera
    ws_data.push(["Estudiante", "Fecha", "Estado"]);

    // Filas
    document.querySelectorAll("#tablaReporte tr").forEach((tr, index) => {
        if(index === 0) return; // saltar encabezado
        const row = [];
        tr.querySelectorAll("td").forEach(td => row.push(td.innerText));
        ws_data.push(row);
    });

    const ws = XLSX.utils.aoa_to_sheet(ws_data);
    XLSX.utils.book_append_sheet(wb, ws, "Asistencia");

    XLSX.writeFile(wb, "Reporte_Asistencia.xlsx");
});
