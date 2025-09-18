import { NextRequest, NextResponse } from 'next/server';
import { parseStringPromise } from 'xml2js';

export async function GET() {
  const username = process.env.SUNHOTELS_USER!;
  const password = process.env.SUNHOTELS_PASS!;
  const soapBody = `
    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
      <soap:Body>
        <GetDestinationsV2 xmlns="http://xml.sunhotels.net/15/">
          <userName>${username}</userName>
          <password>${password}</password>
          <language>en</language>
        </GetDestinationsV2>
      </soap:Body>
    </soap:Envelope>
  `;
  try {
    const response = await fetch("http://xml.sunhotels.net/15/SOAP/NonStaticXMLAPI.asmx", {
      method: "POST",
      headers: {
        "Content-Type": "text/xml; charset=utf-8",
        "SOAPAction": "http://xml.sunhotels.net/15/GetDestinationsV2"
      },
      body: soapBody
    });
    const xml = await response.text();
    const json = await parseStringPromise(xml, { explicitArray: false });

    // Foutafhandeling toegevoegd:
    const result = json?.["soap:Envelope"]?.["soap:Body"]?.["GetDestinationsV2Response"]?.["GetDestinationsV2Result"];
    if (!result || !result.Destinations) {
      return NextResponse.json(
        { error: "No destinations found or invalid Sunhotels credentials/response", debug: json },
        { status: 500 }
      );
    }

    const destinations = result.Destinations.Destination;
    const results = Array.isArray(destinations)
      ? destinations.map((d: any) => ({ id: d.DestinationId, name: d.Name }))
      : [];

    return NextResponse.json({ results });
  } catch (error) {
    return NextResponse.json(
      { error: "Failed to fetch destinations", details: (error as Error).message },
      { status: 500 }
    );
  }
}