import { NextRequest, NextResponse } from 'next/server';
import { parseStringPromise } from 'xml2js';

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const country_id = searchParams.get('country_id');
  const username = process.env.SUNHOTELS_USER!;
  const password = process.env.SUNHOTELS_PASS!;
  const soapBody = `
    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
      <soap:Body>
        <GetResortsV2 xmlns="http://xml.sunhotels.net/15/">
          <userName>${username}</userName>
          <password>${password}</password>
          <destinationId>${country_id}</destinationId>
          <language>en</language>
        </GetResortsV2>
      </soap:Body>
    </soap:Envelope>
  `;
  const response = await fetch("http://xml.sunhotels.net/15/SOAP/NonStaticXMLAPI.asmx", {
    method: "POST",
    headers: {
      "Content-Type": "text/xml; charset=utf-8",
      "SOAPAction": "http://xml.sunhotels.net/15/GetResortsV2"
    },
    body: soapBody
  });
  const xml = await response.text();
  const json = await parseStringPromise(xml, { explicitArray: false });
  const resorts = json["soap:Envelope"]["soap:Body"]["GetResortsV2Response"]["GetResortsV2Result"]["Resorts"]["Resort"];
  const results = Array.isArray(resorts)
    ? resorts.map((r: any) => ({ id: r.ResortId, name: r.Name }))
    : [];
  return NextResponse.json({ results });
}